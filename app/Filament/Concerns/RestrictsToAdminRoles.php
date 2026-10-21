<?php

namespace App\Filament\Concerns;

trait RestrictsToAdminRoles
{
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
