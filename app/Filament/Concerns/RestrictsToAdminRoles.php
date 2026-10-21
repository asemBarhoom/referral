<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait RestrictsToAdminRoles
{
    public static function canViewAny(): bool
    {
        $role = auth()->user()?->role;
        return in_array($role, [User::ROLE_ADMIN, 'super_admin']);
    }
}
