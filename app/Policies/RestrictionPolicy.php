<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Restriction;
use Illuminate\Auth\Access\HandlesAuthorization;

class RestrictionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Restriction');
    }

    public function view(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('View:Restriction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Restriction');
    }

    public function update(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('Update:Restriction');
    }

    public function delete(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('Delete:Restriction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Restriction');
    }

    public function restore(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('Restore:Restriction');
    }

    public function forceDelete(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('ForceDelete:Restriction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Restriction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Restriction');
    }

    public function replicate(AuthUser $authUser, Restriction $restriction): bool
    {
        return $authUser->can('Replicate:Restriction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Restriction');
    }

}