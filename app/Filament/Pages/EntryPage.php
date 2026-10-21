<?php

namespace App\Filament\Pages;

use App\Models\Beneficiary;
use App\Models\Center;
use App\Models\ChangeLog;
use App\Models\Entry;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Size;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Reception: enter the ID, pick projects, save the day's referral.
// Point: enter the ID, record the service for the user's own project, or transfer to another project.
class EntryPage extends Page
{
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
        return in_array(auth()->user()?->role, [User::ROLE_RECEPTION, User::ROLE_POINT], true);
    }

    public function mount(): void
    {
        $this->reset();
    }

    public function getSubheading(): ?string
    {
        return Setting::get($this->isPoint() ? 'hint_point' : 'hint_reception');
    }

    public function isPoint(): bool
    {
        return auth()->user()->isPoint();
    }

    public function center(): ?Center
    {
        return auth()->user()->center;
    }

    public function project(): ?Project
    {
        return $this->isPoint() ? auth()->user()->project : null;
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

        if (! $this->found_beneficiary || ! $this->center()) {
            return;
        }

        if ($this->isPoint()) {
            $this->today_entry = $this->project() ? $this->servedTodayQuery()->first() : null;
            return;
        }

        $this->today_entry = $this->todayReferralQuery($this->found_beneficiary->id)->first();

        $source = $this->today_entry ?? Entry::where('beneficiary_id', $this->found_beneficiary->id)
            ->where('center_id', $this->center()->id)
            ->where('type', Entry::TYPE_RECEPTION)
            ->latest('entry_time')
            ->first();

        $this->selected_projects = $source?->projects->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [];
    }

    public function submit(): void
    {
        if (! $this->searched || strlen((string) $this->identity_number) !== 9) {
            return;
        }

        if (! $this->center()) {
            Notification::make()->title('لا يوجد مركز مرتبط بحسابك')->danger()->send();
            return;
        }

        $this->isPoint() ? $this->recordService() : $this->saveReferral();
    }

    private function saveReferral(): void
    {
        $projectIds = array_values(array_intersect(
            $this->selected_projects,
            array_map('strval', array_keys($this->getCenterProjects())),
        ));

        if (empty($projectIds)) {
            Notification::make()->title('اختر مشروعاً واحداً على الأقل')->danger()->send();
            return;
        }

        $isUpdate = (bool) $this->today_entry;

        DB::transaction(function () use ($projectIds) {
            $beneficiary = $this->resolveBeneficiary();

            if ($this->today_entry) {
                $old = $this->projectIdsOf($this->today_entry);
                $new = collect($projectIds)->sort()->values()->all();
                $this->today_entry->projects()->sync($new);

                if ($old !== $new) {
                    $this->log('updated_entry_projects', $this->today_entry, ['project_ids' => $old], ['project_ids' => $new]);
                }

                return;
            }

            $this->createEntry($beneficiary, Entry::TYPE_RECEPTION)->projects()->sync($projectIds);
        });

        Notification::make()->title($isUpdate ? 'تم تحديث التحويل' : 'تم حفظ التحويل')->success()->send();
        $this->resetForm();
    }

    private function recordService(): void
    {
        $project = $this->project();

        if (! $project) {
            Notification::make()->title('لا يوجد مشروع مرتبط بحسابك')->danger()->send();
            return;
        }

        if ($this->found_beneficiary && $this->servedTodayQuery()->exists()) {
            Notification::make()->title('هذه الحالة خُدمت اليوم في ' . $project->name)->warning()->send();
            return;
        }

        DB::transaction(function () use ($project) {
            $this->createEntry($this->resolveBeneficiary(), Entry::TYPE_POINT)->projects()->sync([$project->id]);
        });

        Notification::make()->title('تم تسجيل الخدمة')->success()->send();
        $this->resetForm();
    }

    public function transferAction(): Action
    {
        return Action::make('transfer')
            ->label('تحويل لمشروع آخر')
            ->icon('heroicon-m-arrows-right-left')
            ->color('gray')
            ->size(Size::ExtraLarge)
            ->visible(fn () => $this->searched && $this->isPoint())
            ->modalHeading('تحويل الحالة لمشروع آخر')
            ->modalDescription(fn () => 'رقم الهوية ' . $this->identity_number . ' — يُستبدل مشروعك في تحويل اليوم بالمشروع المختار.')
            ->modalSubmitActionLabel('تحويل')
            ->modalWidth('md')
            ->schema([
                Select::make('project_id')
                    ->label('المشروع')
                    ->options(fn () => $this->getTransferTargets())
                    ->required(),
            ])
            ->action(fn (array $data) => $this->transfer((int) $data['project_id']));
    }

    private function transfer(int $targetId): void
    {
        $targets = $this->getTransferTargets();

        if (! $this->center() || ! array_key_exists($targetId, $targets) || strlen((string) $this->identity_number) !== 9) {
            Notification::make()->title('تعذّر التحويل')->danger()->send();
            return;
        }

        DB::transaction(function () use ($targetId) {
            $beneficiary = $this->resolveBeneficiary();
            $referral = $this->todayReferralQuery($beneficiary->id)->first();
            $old = $referral ? $this->projectIdsOf($referral) : [];

            $new = collect($old)
                ->reject(fn ($id) => $id === (string) $this->project()?->id)
                ->push((string) $targetId)
                ->unique()->sort()->values()->all();

            $referral ??= $this->createEntry($beneficiary, Entry::TYPE_RECEPTION);
            $referral->projects()->sync($new);

            $this->log('transferred_case', $referral, ['project_ids' => $old], ['project_ids' => $new, 'to_project_id' => $targetId]);
        });

        Notification::make()->title('تم تحويل الحالة إلى ' . $targets[$targetId])->success()->send();
        $this->resetForm();
    }

    /** Active projects of the user's center, in display order. */
    public function getCenterProjects(): array
    {
        return $this->center()?->projects()
            ->where('projects.is_active', true)
            ->wherePivot('is_active', true)
            ->orderBy('projects.display_order')
            ->pluck('projects.name', 'projects.id')
            ->toArray() ?? [];
    }

    public function getTransferTargets(): array
    {
        return array_diff_key($this->getCenterProjects(), array_flip(array_filter([$this->project()?->id])));
    }

    public function getTodayReferralProjects(): array
    {
        if (! $this->found_beneficiary || ! $this->center()) {
            return [];
        }

        return $this->todayReferralQuery($this->found_beneficiary->id)
            ->first()?->projects()->orderBy('display_order')->pluck('name')->all() ?? [];
    }

    public function getLastTransfer(): ?array
    {
        if ($this->isPoint() || ! $this->today_entry) {
            return null;
        }

        $log = ChangeLog::with('user:id,name')
            ->where('action', 'transferred_case')
            ->where('subject_type', Entry::class)
            ->where('subject_id', $this->today_entry->id)
            ->latest('changed_at')
            ->first();

        if (! $log) {
            return null;
        }

        $removed = array_diff($log->old_value['project_ids'] ?? [], $log->new_value['project_ids'] ?? []);
        $names   = Project::whereIn('id', [...$removed, $log->new_value['to_project_id'] ?? 0])->pluck('name', 'id');

        return [
            'by'   => $log->user?->name,
            'from' => collect($removed)->map(fn ($id) => $names[$id] ?? null)->filter()->join('، '),
            'to'   => $names[$log->new_value['to_project_id'] ?? 0] ?? null,
            'at'   => $log->changed_at->format('H:i'),
        ];
    }

    public function getTodayStats(): array
    {
        $query = Entry::where('staff_id', auth()->id())
            ->where('type', $this->isPoint() ? Entry::TYPE_POINT : Entry::TYPE_RECEPTION)
            ->where('entry_date', $this->today());

        $total    = (clone $query)->count();
        $newCount = (clone $query)->where('is_new', true)->count();

        return compact('total', 'newCount');
    }

    private function resolveBeneficiary(): Beneficiary
    {
        $beneficiary = $this->found_beneficiary ?? Beneficiary::firstOrCreate(
            ['identity_number' => $this->identity_number],
            ['name' => filled($this->name) ? $this->name : null, 'first_entry_date' => $this->today()],
        );

        if (! $beneficiary->wasRecentlyCreated && filled($this->name) && $this->name !== $beneficiary->name) {
            $this->log('updated_name', $beneficiary, ['name' => $beneficiary->name], ['name' => $this->name]);
            $beneficiary->update(['name' => $this->name]);
        }

        return $beneficiary;
    }

    private function createEntry(Beneficiary $beneficiary, string $type): Entry
    {
        $now = Carbon::now('Asia/Gaza');

        return Entry::create([
            'beneficiary_id' => $beneficiary->id,
            'center_id'      => $this->center()->id,
            'staff_id'       => auth()->id(),
            'type'           => $type,
            'is_new'         => $beneficiary->wasRecentlyCreated,
            'entry_time'     => $now,
            'entry_date'     => $now->toDateString(),
        ]);
    }

    // The center's referral for this case today (one per center per day).
    private function todayReferralQuery(int $beneficiaryId)
    {
        return Entry::where('beneficiary_id', $beneficiaryId)
            ->where('center_id', $this->center()->id)
            ->where('entry_date', $this->today())
            ->where('type', Entry::TYPE_RECEPTION)
            ->latest('entry_time');
    }

    // A service in the user's project for this case today, by any point staff.
    private function servedTodayQuery()
    {
        return Entry::with('staff:id,name')
            ->where('beneficiary_id', $this->found_beneficiary->id)
            ->where('entry_date', $this->today())
            ->where('type', Entry::TYPE_POINT)
            ->whereHas('projects', fn ($q) => $q->where('projects.id', $this->project()->id))
            ->latest('entry_time');
    }

    private function projectIdsOf(Entry $entry): array
    {
        return $entry->projects()->pluck('projects.id')->map(fn ($id) => (string) $id)->sort()->values()->all();
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
