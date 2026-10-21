<?php

namespace App\Filament\Pages;

use App\Models\Beneficiary;
use App\Models\Center;
use App\Models\ChangeLog;
use App\Models\Entry;
use App\Models\EntryProject;
use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EntryPage extends Page
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected string $view = 'filament.pages.entry-page';
    protected static ?string $title = 'شاشة الإدخال';
    protected static ?int $navigationSort = 1;

    public ?string $identity_number = null;
    public ?string $name = null;
    public array $selected_projects = [];

    public ?Beneficiary $found_beneficiary = null;
    public ?Entry $today_entry = null;
    public bool $searched = false;

    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;
        return in_array($role, [
            User::ROLE_RECEPTION,
            User::ROLE_POINT,
            User::ROLE_ADMIN,
            'super_admin',
        ]);
    }

    public function mount(): void
    {
        $this->reset();
    }

    public function searchBeneficiary(): void
    {
        $normalized = preg_replace_callback('/[٠-٩]/u', fn($m) => mb_ord($m[0], 'UTF-8') - mb_ord('٠', 'UTF-8'), $this->identity_number ?? '');
        $normalized = preg_replace('/\s+/', '', $normalized);

        if (strlen($normalized) !== 9 || !ctype_digit($normalized)) {
            Notification::make()->title('رقم الهوية يجب أن يكون 9 أرقام')->danger()->send();
            return;
        }

        $this->identity_number = $normalized;
        $this->found_beneficiary = Beneficiary::where('identity_number', $normalized)->first();
        $this->searched = true;

        $gazaDate = Carbon::now('Asia/Gaza')->toDateString();

        if ($this->found_beneficiary) {
            // Check if already entered today
            $this->today_entry = Entry::where('beneficiary_id', $this->found_beneficiary->id)
                ->where('entry_date', $gazaDate)
                ->where('center_id', $this->userCenterId())
                ->latest('entry_time')
                ->first();

            // Pre-select projects from last entry
            if ($this->today_entry) {
                $this->selected_projects = $this->today_entry->projects->pluck('id')->toArray();
            } else {
                $lastEntry = Entry::where('beneficiary_id', $this->found_beneficiary->id)
                    ->where('center_id', $this->userCenterId())
                    ->latest('entry_time')
                    ->first();
                $this->selected_projects = $lastEntry?->projects->pluck('id')->toArray() ?? [];
            }

            $this->name = $this->found_beneficiary->name;
        } else {
            $this->today_entry = null;
            $this->selected_projects = [];
            $this->name = null;
        }
    }

    public function submit(): void
    {
        if (!$this->searched) return;

        $normalized = $this->identity_number;
        if (!$normalized || strlen($normalized) !== 9) return;

        if (empty($this->selected_projects)) {
            Notification::make()->title('اختر مشروعاً واحداً على الأقل')->danger()->send();
            return;
        }

        $user = auth()->user();
        $gazaNow = Carbon::now('Asia/Gaza');
        $centerId = $this->userCenterId();

        DB::transaction(function () use ($normalized, $user, $gazaNow, $centerId) {
            $isNew = false;

            if (!$this->found_beneficiary) {
                $this->found_beneficiary = Beneficiary::create([
                    'identity_number' => $normalized,
                    'name' => filled($this->name) ? $this->name : null,
                    'first_entry_date' => $gazaNow->toDateString(),
                ]);
                $isNew = true;
            } else {
                // Update name if provided and changed
                if (filled($this->name) && $this->name !== $this->found_beneficiary->name) {
                    $old = $this->found_beneficiary->name;
                    $this->found_beneficiary->update(['name' => $this->name]);
                    ChangeLog::create([
                        'user_id' => $user->id,
                        'action' => 'updated_name',
                        'subject_type' => Beneficiary::class,
                        'subject_id' => $this->found_beneficiary->id,
                        'old_value' => ['name' => $old],
                        'new_value' => ['name' => $this->name],
                        'changed_at' => $gazaNow,
                    ]);
                }
            }

            if ($this->today_entry) {
                // Update projects on same-day same-center entry
                $old = $this->today_entry->projects->pluck('id')->toArray();
                $this->today_entry->projects()->sync($this->selected_projects);
                if (array_diff($old, $this->selected_projects) || array_diff($this->selected_projects, $old)) {
                    ChangeLog::create([
                        'user_id' => $user->id,
                        'action' => 'updated_entry_projects',
                        'subject_type' => Entry::class,
                        'subject_id' => $this->today_entry->id,
                        'old_value' => ['project_ids' => $old],
                        'new_value' => ['project_ids' => $this->selected_projects],
                        'changed_at' => $gazaNow,
                    ]);
                }
            } else {
                $entry = Entry::create([
                    'beneficiary_id' => $this->found_beneficiary->id,
                    'center_id' => $centerId,
                    'staff_id' => $user->id,
                    'type' => $user->role === User::ROLE_RECEPTION ? Entry::TYPE_RECEPTION : Entry::TYPE_POINT,
                    'is_new' => $isNew,
                    'entry_time' => $gazaNow,
                    'entry_date' => $gazaNow->toDateString(),
                ]);

                // For point staff: check duplicate per project per day
                if ($user->role === User::ROLE_POINT) {
                    $todayEntryIds = Entry::where('beneficiary_id', $this->found_beneficiary->id)
                        ->where('entry_date', $gazaNow->toDateString())
                        ->pluck('id');
                    $alreadyProjects = EntryProject::whereIn('entry_id', $todayEntryIds)
                        ->whereIn('project_id', $this->selected_projects)
                        ->pluck('project_id')
                        ->toArray();
                    $newProjects = array_diff($this->selected_projects, $alreadyProjects);
                    if (empty($newProjects)) {
                        $entry->delete();
                        Notification::make()->title('هذه الحالة أُدخلت اليوم في جميع المشاريع المختارة')->warning()->send();
                        return;
                    }
                    $entry->projects()->sync($newProjects);
                } else {
                    $entry->projects()->sync($this->selected_projects);
                }
            }
        });

        Notification::make()
            ->title($this->today_entry ? 'تم تحديث الإدخال' : 'تم الإدخال بنجاح')
            ->success()
            ->send();

        $this->reset(['identity_number', 'name', 'selected_projects', 'found_beneficiary', 'today_entry', 'searched']);
    }

    public function getAvailableProjects(): array
    {
        $user = auth()->user();
        $centerId = $this->userCenterId();

        if ($user->role === User::ROLE_POINT) {
            return $user->projects()->where('is_active', true)->orderBy('display_order')->pluck('name', 'id')->toArray();
        }

        // reception and admin see center projects
        $center = Center::find($centerId);
        if (!$center) return [];
        return $center->projects()->where('is_active', true)->orderBy('display_order')->pluck('projects.name', 'projects.id')->toArray();
    }

    private function userCenterId(): ?int
    {
        return auth()->user()?->centers()->first()?->id;
    }

    public function getTodayStats(): array
    {
        $user = auth()->user();
        $gazaDate = Carbon::now('Asia/Gaza')->toDateString();
        $centerId = $this->userCenterId();

        $query = Entry::where('staff_id', $user->id)
            ->where('entry_date', $gazaDate);

        if ($centerId) $query->where('center_id', $centerId);

        $total = $query->count();
        $newCount = $query->where('is_new', true)->count();

        return compact('total', 'newCount');
    }
}
