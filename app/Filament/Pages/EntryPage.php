<?php

namespace App\Filament\Pages;

use App\Models\Beneficiary;
use App\Models\Center;
use App\Models\ChangeLog;
use App\Models\Entry;
use App\Models\EntryProject;
use App\Models\Setting;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EntryPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected string $view = 'filament.pages.entry-page';
    protected static ?string $title = 'شاشة الإدخال';
    protected static ?int $navigationSort = 1;

    public ?string $identity_number = null;
    public ?string $name = null;
    public array $selected_projects = [];
    public ?int $center_id = null;

    public ?Beneficiary $found_beneficiary = null;
    public ?Entry $today_entry = null;
    public bool $searched = false;

    private ?array $userCenters = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_RECEPTION, User::ROLE_POINT, User::ROLE_ADMIN], true);
    }

    public function mount(): void
    {
        $this->reset();
        $this->center_id = $this->userCenterId();
    }

    public function getSubheading(): ?string
    {
        return Setting::get($this->entryType() === Entry::TYPE_RECEPTION ? 'hint_reception' : 'hint_point');
    }

    public function updatedCenterId(): void
    {
        if ($this->center_id && array_key_exists($this->center_id, $this->getUserCenters())) {
            session(['entry_center_id' => $this->center_id]);
        }

        $this->center_id = $this->userCenterId();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['identity_number', 'name', 'selected_projects', 'found_beneficiary', 'today_entry', 'searched']);
        $this->dispatch('entry-saved');
    }

    public function searchBeneficiary(): void
    {
        $normalized = preg_replace_callback('/[٠-٩]/u', fn ($m) => mb_ord($m[0], 'UTF-8') - mb_ord('٠', 'UTF-8'), $this->identity_number ?? '');
        $normalized = preg_replace('/\s+/', '', $normalized);

        if (strlen($normalized) !== 9 || ! ctype_digit($normalized)) {
            Notification::make()->title('رقم الهوية يجب أن يكون 9 أرقام')->danger()->send();
            return;
        }

        $this->identity_number   = $normalized;
        $this->found_beneficiary = Beneficiary::where('identity_number', $normalized)->first();
        $this->today_entry       = null;
        $this->selected_projects = [];
        $this->name              = $this->found_beneficiary?->name;
        $this->searched          = true;

        if (! $this->found_beneficiary) {
            return;
        }

        $this->today_entry = $this->ownEntryTodayQuery()->first();

        $source = $this->today_entry ?? Entry::where('beneficiary_id', $this->found_beneficiary->id)
            ->where('center_id', $this->userCenterId())
            ->latest('entry_time')
            ->first();

        $this->selected_projects = $source?->projects->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
    }

    public function submit(): void
    {
        if (! $this->searched || strlen((string) $this->identity_number) !== 9) {
            return;
        }

        $centerId = $this->userCenterId();
        if (! $centerId) {
            Notification::make()->title('لا يوجد مركز مرتبط بحسابك')->danger()->send();
            return;
        }

        $projectIds = array_values(array_intersect(
            $this->selected_projects,
            array_map('strval', array_keys($this->getAvailableProjects())),
        ));

        if (empty($projectIds)) {
            Notification::make()->title('اختر مشروعاً واحداً على الأقل')->danger()->send();
            return;
        }

        // A point service is recorded once per project per day, whoever served it.
        $skipped = [];
        if (! $this->today_entry && $this->found_beneficiary && $this->entryType() === Entry::TYPE_POINT) {
            $servedEntryIds = Entry::where('beneficiary_id', $this->found_beneficiary->id)
                ->where('entry_date', $this->today())
                ->where('type', Entry::TYPE_POINT)
                ->pluck('id');

            $skipped = EntryProject::whereIn('entry_id', $servedEntryIds)
                ->whereIn('project_id', $projectIds)
                ->pluck('project_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $projectIds = array_values(array_diff($projectIds, $skipped));

            if (empty($projectIds)) {
                Notification::make()->title('هذه الحالة خُدمت اليوم في جميع المشاريع المختارة')->warning()->send();
                return;
            }
        }

        $isUpdate = (bool) $this->today_entry;

        DB::transaction(fn () => $this->persist($centerId, $projectIds));

        $notification = Notification::make()
            ->title($isUpdate ? 'تم تحديث الإدخال' : 'تم الإدخال بنجاح')
            ->success();

        if ($skipped) {
            $names = array_intersect_key($this->getAvailableProjects(), array_flip($skipped));
            $notification->body('تم تخطي مشاريع مسجلة اليوم: ' . implode('، ', $names));
        }

        $notification->send();
        $this->resetForm();
    }

    private function persist(int $centerId, array $projectIds): void
    {
        $user = auth()->user();
        $now  = Carbon::now('Asia/Gaza');

        $beneficiary = $this->found_beneficiary ?? Beneficiary::firstOrCreate(
            ['identity_number' => $this->identity_number],
            ['name' => filled($this->name) ? $this->name : null, 'first_entry_date' => $now->toDateString()],
        );
        $isNew = $beneficiary->wasRecentlyCreated;

        if (! $isNew && filled($this->name) && $this->name !== $beneficiary->name) {
            $this->log('updated_name', $beneficiary, ['name' => $beneficiary->name], ['name' => $this->name]);
            $beneficiary->update(['name' => $this->name]);
        }

        if ($this->today_entry) {
            $old = $this->today_entry->projects()->pluck('projects.id')->map(fn ($id) => (string) $id)->sort()->values()->all();
            $this->today_entry->projects()->sync($projectIds);
            $new = collect($projectIds)->sort()->values()->all();

            if ($old !== $new) {
                $this->log('updated_entry_projects', $this->today_entry, ['project_ids' => $old], ['project_ids' => $new]);
            }

            return;
        }

        Entry::create([
            'beneficiary_id' => $beneficiary->id,
            'center_id'      => $centerId,
            'staff_id'       => $user->id,
            'type'           => $this->entryType(),
            'is_new'         => $isNew,
            'entry_time'     => $now,
            'entry_date'     => $now->toDateString(),
        ])->projects()->sync($projectIds);
    }

    public function getAvailableProjects(): array
    {
        $center = Center::find($this->userCenterId());
        if (! $center) {
            return [];
        }

        $userProjectIds = auth()->user()->projects()->pluck('projects.id');

        return $center->projects()
            ->where('projects.is_active', true)
            ->wherePivot('is_active', true)
            ->when($userProjectIds->isNotEmpty(), fn ($q) => $q->whereIn('projects.id', $userProjectIds))
            ->orderBy('projects.display_order')
            ->pluck('projects.name', 'projects.id')
            ->toArray();
    }

    public function getUserCenters(): array
    {
        return $this->userCenters ??= auth()->user()->centers()
            ->where('centers.is_active', true)
            ->orderBy('centers.id')
            ->pluck('centers.name', 'centers.id')
            ->toArray();
    }

    public function userCenterId(): ?int
    {
        $centers = $this->getUserCenters();
        $chosen  = session('entry_center_id');

        return $chosen && array_key_exists($chosen, $centers) ? $chosen : array_key_first($centers);
    }

    public function getTodayStats(): array
    {
        $query = Entry::where('staff_id', auth()->id())
            ->where('entry_date', $this->today())
            ->when($this->userCenterId(), fn ($q, $centerId) => $q->where('center_id', $centerId));

        $total    = (clone $query)->count();
        $newCount = (clone $query)->where('is_new', true)->count();

        return compact('total', 'newCount');
    }

    private function entryType(): string
    {
        return auth()->user()?->role === User::ROLE_RECEPTION ? Entry::TYPE_RECEPTION : Entry::TYPE_POINT;
    }

    // Reception shares one arrival entry per center per day; point staff only touch their own.
    private function ownEntryTodayQuery()
    {
        return Entry::where('beneficiary_id', $this->found_beneficiary->id)
            ->where('entry_date', $this->today())
            ->where('center_id', $this->userCenterId())
            ->where('type', $this->entryType())
            ->when($this->entryType() === Entry::TYPE_POINT, fn ($q) => $q->where('staff_id', auth()->id()))
            ->latest('entry_time');
    }

    private function today(): string
    {
        return Carbon::now('Asia/Gaza')->toDateString();
    }

    private function log(string $action, $subject, array $old, array $new): void
    {
        ChangeLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject::class,
            'subject_id'   => $subject->id,
            'old_value'    => $old,
            'new_value'    => $new,
            'changed_at'   => Carbon::now('Asia/Gaza'),
        ]);
    }
}
