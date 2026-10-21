<?php

namespace App\Filament\Widgets;

use App\Models\Entry;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DailyStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $user   = auth()->user();
        $today  = Carbon::now('Asia/Gaza')->toDateString();
        $center = $user->centers()->first();

        $role = $user->role;

        if ($role === User::ROLE_ADMIN || $role === 'super_admin') {
            return $this->adminStats($today, $center);
        }

        if ($role === User::ROLE_POINT) {
            return $this->pointStats($today, $user);
        }

        return $this->receptionStats($today, $center, $user);
    }

    private function receptionStats(string $today, $center, $user): array
    {
        $query = Entry::where('staff_id', $user->id)
            ->where('entry_date', $today)
            ->where('type', Entry::TYPE_RECEPTION);

        $total    = $query->count();
        $newCount = $query->where('is_new', true)->count();

        // Project breakdown
        $projectCounts = Entry::where('staff_id', $user->id)
            ->where('entry_date', $today)
            ->where('type', Entry::TYPE_RECEPTION)
            ->join('entry_projects', 'entries.id', '=', 'entry_projects.entry_id')
            ->join('projects', 'entry_projects.project_id', '=', 'projects.id')
            ->groupBy('projects.name')
            ->selectRaw('projects.name, count(*) as cnt')
            ->pluck('cnt', 'name')
            ->map(fn($v) => (string)$v)
            ->toArray();

        $stats = [
            Stat::make('إجمالي الحالات', $total)->color('primary')->icon('heroicon-o-users'),
            Stat::make('جديدة', $newCount)->color('success')->icon('heroicon-o-user-plus'),
            Stat::make('متكررة', $total - $newCount)->color('gray')->icon('heroicon-o-arrow-path'),
        ];

        foreach ($projectCounts as $pname => $cnt) {
            $stats[] = Stat::make($pname, $cnt)->color('info');
        }

        return $stats;
    }

    private function pointStats(string $today, $user): array
    {
        $query = Entry::where('staff_id', $user->id)
            ->where('entry_date', $today)
            ->where('type', Entry::TYPE_POINT);

        $total    = $query->count();
        $newCount = $query->where('is_new', true)->count();

        $first = Entry::where('staff_id', $user->id)->where('entry_date', $today)->where('type', Entry::TYPE_POINT)->min('entry_time');
        $last  = Entry::where('staff_id', $user->id)->where('entry_date', $today)->where('type', Entry::TYPE_POINT)->max('entry_time');

        return [
            Stat::make('الحالات المخدومة', $total)->color('primary')->icon('heroicon-o-check-circle'),
            Stat::make('جديدة على النظام', $newCount)->color('success')->icon('heroicon-o-user-plus'),
            Stat::make('أول حالة', $first ? Carbon::parse($first)->setTimezone('Asia/Gaza')->format('H:i') : '—')->color('gray'),
            Stat::make('آخر حالة', $last  ? Carbon::parse($last)->setTimezone('Asia/Gaza')->format('H:i') : '—')->color('gray'),
        ];
    }

    private function adminStats(string $today, $center): array
    {
        $centerId = $center?->id;
        $base = Entry::where('entry_date', $today);
        if ($centerId) $base->where('center_id', $centerId);

        $reception  = (clone $base)->where('type', Entry::TYPE_RECEPTION)->count();
        $newCases   = (clone $base)->where('is_new', true)->count();
        $pointTotal = (clone $base)->where('type', Entry::TYPE_POINT)->count();
        $activeStaff = (clone $base)->distinct('staff_id')->count('staff_id');

        return [
            Stat::make('حالات الاستقبال', $reception)->color('primary')->icon('heroicon-o-users'),
            Stat::make('حالات جديدة', $newCases)->color('success')->icon('heroicon-o-user-plus'),
            Stat::make('حالات النقاط', $pointTotal)->color('info')->icon('heroicon-o-check-circle'),
            Stat::make('موظفون نشطون', $activeStaff)->color('warning')->icon('heroicon-o-user-group'),
        ];
    }
}
