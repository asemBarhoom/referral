<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\IdentityType;
use Illuminate\Auth\Access\HandlesAuthorization;

class IdentityTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IdentityType');
    }

    public function view(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('View:IdentityType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IdentityType');
    }

    public function update(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('Update:IdentityType');
    }

    public function delete(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('Delete:IdentityType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:IdentityType');
    }

    public function restore(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('Restore:IdentityType');
    }

    public function forceDelete(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('ForceDelete:IdentityType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:IdentityType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:IdentityType');
    }

    public function replicate(AuthUser $authUser, IdentityType $identityType): bool
    {
        return $authUser->can('Replicate:IdentityType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:IdentityType');
    }

}