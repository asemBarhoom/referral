<?php

namespace App\Filament\Widgets;

use App\Models\BeneficiaryProject;
use App\Models\ReceptionVisit;
use App\Models\ServiceRecord;
use App\Models\VisitReferral;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DailyStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user   = auth()->user();
        $center = $user->centers()->first();
        $today  = Carbon::now('Asia/Gaza')->toDateString();

        if (!$center) {
            return [
                Stat::make('', 'لم يتم تعيين مركز لحسابك')->color('warning'),
            ];
        }

        $role = $user->role;

        if ($role === \App\Models\User::ROLE_PROJECT_STAFF) {
            return $this->projectStats($center, $today, $user);
        }

        return $this->receptionStats($center, $today);
    }

    private function receptionStats($center, string $today): array
    {
        $visits = ReceptionVisit::where('center_id', $center->id)
            ->where('visit_date', $today)
            ->where('status', ReceptionVisit::STATUS_VALID)
            ->count();

        $newCases = ReceptionVisit::where('center_id', $center->id)
            ->where('visit_date', $today)
            ->where('status', ReceptionVisit::STATUS_VALID)
            ->where('type', ReceptionVisit::TYPE_NEW)
            ->count();

        $referrals = VisitReferral::whereHas('visit', fn($q) =>
            $q->where('center_id', $center->id)
              ->where('visit_date', $today)
              ->where('status', ReceptionVisit::STATUS_VALID)
        )->count();

        $served = ServiceRecord::where('center_id', $center->id)
            ->where('service_date', $today)
            ->where('review_status', '!=', ServiceRecord::REVIEW_REJECTED)
            ->distinct('beneficiary_id')
            ->count('beneficiary_id');

        return [
            Stat::make('الزيارات', $visits)->color('primary')->icon('heroicon-o-users'),
            Stat::make('حالات جديدة', $newCases)->color('success')->icon('heroicon-o-user-plus'),
            Stat::make('التحويلات', $referrals)->color('info')->icon('heroicon-o-arrow-right-circle'),
            Stat::make('المخدومون', $served)->color('warning')->icon('heroicon-o-check-circle'),
        ];
    }

    private function projectStats($center, string $today, $user): array
    {
        $project = $user->projects()->first();
        if (!$project) {
            return [Stat::make('', 'لم يتم تعيين مشروع لحسابك')->color('warning')];
        }

        $served = ServiceRecord::where('center_id', $center->id)
            ->where('project_id', $project->id)
            ->where('service_date', $today)
            ->where('review_status', '!=', ServiceRecord::REVIEW_REJECTED)
            ->count();

        $exceptional = ServiceRecord::where('center_id', $center->id)
            ->where('project_id', $project->id)
            ->where('service_date', $today)
            ->where('is_exceptional', true)
            ->count();

        $referred = VisitReferral::whereHas('visit', fn($q) =>
            $q->where('center_id', $center->id)
              ->where('visit_date', $today)
              ->where('status', ReceptionVisit::STATUS_VALID)
        )->whereHas('beneficiaryProject', fn($q) =>
            $q->where('project_id', $project->id)
        )->count();

        $notServed = max(0, $referred - $served);

        return [
            Stat::make('خُدمت', $served)->color('success')->icon('heroicon-o-check-circle'),
            Stat::make('محوّلة ولم تُخدم', $notServed)->color('warning')->icon('heroicon-o-clock'),
            Stat::make('استثنائي', $exceptional)->color('danger')->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
