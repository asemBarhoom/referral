<?php

namespace App\Filament\Pages;

use App\Models\Beneficiary;
use App\Models\BeneficiaryProject;
use App\Models\Center;
use App\Models\IdentityType;
use App\Models\Project;
use App\Services\BeneficiaryMatchService;
use App\Services\ReceptionService;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ReceptionPage extends Page
{
    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;
        return in_array($role, [
            User::ROLE_RECEPTION,
            User::ROLE_CENTER_ADMIN,
            User::ROLE_DASHBOARD_ADMIN,
            'super_admin',
        ]);
    }

    protected string $view = 'filament.pages.reception-page';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';
    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';
    protected static ?string $navigationLabel = 'الاستقبال';
    protected static ?string $title = 'واجهة الاستقبال';
    protected static ?int $navigationSort = 1;

    public string $identityInput = '';
    public ?array $lookupResult = null;
    public array $selectedProjectIds = [];

    // New beneficiary fields
    public string $newName = '';
    public string $newPhone = '';
    public ?int $newIdentityTypeId = null;
    public ?string $newDob = null;

    public function getUserCenter(): ?Center
    {
        return auth()->user()->centers()->first();
    }

    public function getIdentityTypes(): array
    {
        return IdentityType::where('is_active', true)->pluck('printed_name', 'id')->toArray();
    }

    public function getCenterProjects(): array
    {
        $center = $this->getUserCenter();
        if (!$center) {
            return [];
        }
        return Project::whereHas('centers', fn($q) => $q->where('centers.id', $center->id)->where('center_projects.is_active', true))
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedIdentityInput(): void
    {
        $normalized = BeneficiaryMatchService::normalize($this->identityInput);
        if (strlen($normalized) === 9) {
            $this->search();
        } else {
            $this->lookupResult = null;
        }
    }

    public function search(): void
    {
        $center = $this->getUserCenter();
        if (!$center) {
            Notification::make()->title('لم يتم تعيين مركز لحسابك')->warning()->send();
            return;
        }

        $result = BeneficiaryMatchService::lookupForReception($this->identityInput, $center->id);
        $this->lookupResult = $result;

        BeneficiaryMatchService::log(
            $result['identity_number'] ?? BeneficiaryMatchService::normalize($this->identityInput),
            auth()->id(),
            $center->id,
            $result['code']
        );

        // Pre-select active center projects for follow-up visit
        if ($result['code'] === 'FOUND' && !$result['today_visit']) {
            $this->selectedProjectIds = BeneficiaryProject::where('beneficiary_id', $result['beneficiary']->id)
                ->where('center_id', $center->id)
                ->whereIn('status', [BeneficiaryProject::STATUS_ACTIVE])
                ->pluck('project_id')
                ->toArray();
        }
    }

    public function registerVisit(): void
    {
        $center = $this->getUserCenter();
        if (!$center || !$this->lookupResult || $this->lookupResult['code'] !== 'FOUND') {
            return;
        }

        if (empty($this->selectedProjectIds)) {
            Notification::make()->title('اختر مشروعاً واحداً على الأقل')->warning()->send();
            return;
        }

        $beneficiary = $this->lookupResult['beneficiary'];

        try {
            ReceptionService::registerVisit(
                $beneficiary,
                $center->id,
                auth()->id(),
                $this->selectedProjectIds
            );

            Notification::make()->title('تم تسجيل الزيارة بنجاح')->success()->send();
            $this->reset(['identityInput', 'lookupResult', 'selectedProjectIds']);
        } catch (\Throwable $e) {
            Notification::make()->title('حدث خطأ أثناء التسجيل')->body($e->getMessage())->danger()->send();
        }
    }

    public function createAndRegister(): void
    {
        $center = $this->getUserCenter();
        if (!$center) {
            return;
        }

        $this->validate([
            'newName'           => 'required|string|min:3',
            'newPhone'          => 'required|string',
            'newIdentityTypeId' => 'required|integer',
        ], [
            'newName.required'           => 'الاسم مطلوب',
            'newName.min'                => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'newPhone.required'          => 'رقم الجوال مطلوب',
            'newIdentityTypeId.required' => 'نوع الهوية مطلوب',
        ]);

        if (empty($this->selectedProjectIds)) {
            Notification::make()->title('اختر مشروعاً واحداً على الأقل')->warning()->send();
            return;
        }

        $normalized = BeneficiaryMatchService::normalize($this->identityInput);

        if (Beneficiary::where('identity_number', $normalized)->exists()) {
            Notification::make()->title('رقم الهوية مسجل مسبقاً')->danger()->send();
            return;
        }

        try {
            ReceptionService::createAndRegister(
                $normalized,
                $this->newIdentityTypeId,
                $this->newName,
                $this->newPhone,
                $this->newDob ?: null,
                $center->id,
                auth()->id(),
                $this->selectedProjectIds
            );

            Notification::make()->title('تم تسجيل الحالة والزيارة بنجاح')->success()->send();
            $this->reset(['identityInput', 'lookupResult', 'selectedProjectIds', 'newName', 'newPhone', 'newIdentityTypeId', 'newDob']);
        } catch (\Throwable $e) {
            Notification::make()->title('حدث خطأ أثناء التسجيل')->body($e->getMessage())->danger()->send();
        }
    }
}
