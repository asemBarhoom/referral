<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait RestrictsToAdminRoles
{
    protected static function adminRoles(): array
    {
        return [User::ROLE_CENTER_ADMIN, User::ROLE_DASHBOARD_ADMIN, 'super_admin'];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, static::adminRoles());
    }
}
