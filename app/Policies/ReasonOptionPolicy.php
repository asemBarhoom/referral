<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReasonOption;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReasonOptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReasonOption');
    }

    public function view(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('View:ReasonOption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReasonOption');
    }

    public function update(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('Update:ReasonOption');
    }

    public function delete(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('Delete:ReasonOption');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReasonOption');
    }

    public function restore(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('Restore:ReasonOption');
    }

    public function forceDelete(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('ForceDelete:ReasonOption');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReasonOption');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReasonOption');
    }

    public function replicate(AuthUser $authUser, ReasonOption $reasonOption): bool
    {
        return $authUser->can('Replicate:ReasonOption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReasonOption');
    }

}