<?php

namespace App\Filament\Pages;

use App\Models\Center;
use App\Models\Project;
use App\Models\ReasonOption;
use App\Models\ServiceRecord;
use App\Services\BeneficiaryMatchService;
use App\Services\ProjectServiceConfirm;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProjectServicePage extends Page
{
    protected string $view = 'filament.pages.project-service-page';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';
    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';
    protected static ?string $navigationLabel = 'نقطة المشروع';
    protected static ?string $title = 'واجهة نقطة المشروع';
    protected static ?int $navigationSort = 2;

    public string $identityInput = '';
    public ?array $matchResult = null;

    // Exceptional service
    public bool $showExceptionalForm = false;
    public ?int $exceptionReasonId = null;
    public string $exceptionNote = '';

    public function getUserCenter(): ?Center
    {
        return auth()->user()->centers()->first();
    }

    public function getUserProject(): ?Project
    {
        return auth()->user()->projects()->first();
    }

    public function getExceptionReasons(): array
    {
        return ReasonOption::where('category', 'exception')
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedIdentityInput(): void
    {
        $normalized = BeneficiaryMatchService::normalize($this->identityInput);
        if (strlen($normalized) === 9) {
            $this->match();
        } else {
            $this->matchResult = null;
            $this->showExceptionalForm = false;
        }
    }

    public function match(): void
    {
        $center  = $this->getUserCenter();
        $project = $this->getUserProject();

        if (!$center || !$project) {
            Notification::make()->title('لم يتم تعيين مركز أو مشروع لحسابك')->warning()->send();
            return;
        }

        $result = BeneficiaryMatchService::matchForService($this->identityInput, $project->id, $center->id);
        $this->matchResult = $result;
        $this->showExceptionalForm = false;

        BeneficiaryMatchService::log(
            $result['identity_number'] ?? BeneficiaryMatchService::normalize($this->identityInput),
            auth()->id(),
            $center->id,
            $result['code']
        );
    }

    public function confirmService(): void
    {
        $center  = $this->getUserCenter();
        $project = $this->getUserProject();
        if (!$center || !$project || !$this->matchResult) {
            return;
        }

        $allowedCodes = [ServiceRecord::CODE_MATCHED, ServiceRecord::CODE_PENDING_ENROLLMENT];
        if (!in_array($this->matchResult['code'], $allowedCodes)) {
            Notification::make()->title('لا يمكن تأكيد الخدمة لهذه الحالة')->danger()->send();
            return;
        }

        try {
            ProjectServiceConfirm::confirm($this->matchResult, $project->id, $center->id, auth()->id());
            Notification::make()->title('تم تأكيد الخدمة')->success()->send();
            $this->reset(['identityInput', 'matchResult', 'showExceptionalForm']);
        } catch (\Throwable $e) {
            Notification::make()->title('حدث خطأ')->body($e->getMessage())->danger()->send();
        }
    }

    public function confirmExceptionalService(): void
    {
        $center  = $this->getUserCenter();
        $project = $this->getUserProject();
        if (!$center || !$project || !$this->matchResult) {
            return;
        }

        if (!$project->allow_exceptional_service) {
            Notification::make()->title('الخدمة الاستثنائية غير مفعّلة لهذا المشروع')->danger()->send();
            return;
        }

        $this->validate([
            'exceptionReasonId' => 'required|integer',
            'exceptionNote'     => 'required|string|min:5',
        ], [
            'exceptionReasonId.required' => 'سبب الاستثناء مطلوب',
            'exceptionNote.required'     => 'الملاحظة مطلوبة',
            'exceptionNote.min'          => 'الملاحظة يجب أن تكون 5 أحرف على الأقل',
        ]);

        try {
            ProjectServiceConfirm::confirmExceptional(
                $this->matchResult,
                $project->id,
                $center->id,
                auth()->id(),
                $this->exceptionReasonId,
                $this->exceptionNote
            );

            Notification::make()->title('تم تسجيل الخدمة الاستثنائية وإحالتها للمراجعة')->success()->send();
            $this->reset(['identityInput', 'matchResult', 'showExceptionalForm', 'exceptionReasonId', 'exceptionNote']);
        } catch (\Throwable $e) {
            Notification::make()->title('حدث خطأ')->body($e->getMessage())->danger()->send();
        }
    }
}
