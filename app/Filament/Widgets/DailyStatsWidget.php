<?php

namespace App\Filament\Widgets;

use App\Models\Entry;
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
        $user  = auth()->user();
        $today = Carbon::now('Asia/Gaza')->toDateString();

        return match ($user->role) {
            User::ROLE_ADMIN => $this->adminStats($today),
            User::ROLE_POINT => $this->pointStats($today, $user),
            default          => $this->receptionStats($today, $user),
        };
    }

    private function receptionStats(string $today, $user): array
    {
        $query = Entry::where('staff_id', $user->id)
            ->where('entry_date', $today)
            ->where('type', Entry::TYPE_RECEPTION);

        $total    = (clone $query)->count();
        $newCount = (clone $query)->where('is_new', true)->count();

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

        $total    = (clone $query)->count();
        $newCount = (clone $query)->where('is_new', true)->count();
        $first    = (clone $query)->min('entry_time');
        $last     = (clone $query)->max('entry_time');

        return [
            Stat::make('الحالات المخدومة', $total)->color('primary')->icon('heroicon-o-check-circle'),
            Stat::make('جديدة على النظام', $newCount)->color('success')->icon('heroicon-o-user-plus'),
            Stat::make('أول حالة', $first ? Carbon::parse($first)->format('H:i') : '—')->color('gray'),
            Stat::make('آخر حالة', $last ? Carbon::parse($last)->format('H:i') : '—')->color('gray'),
        ];
    }

    private function adminStats(string $today): array
    {
        $base = Entry::where('entry_date', $today);

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
