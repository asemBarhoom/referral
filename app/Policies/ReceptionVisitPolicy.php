<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReceptionVisit;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReceptionVisitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReceptionVisit');
    }

    public function view(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('View:ReceptionVisit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReceptionVisit');
    }

    public function update(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('Update:ReceptionVisit');
    }

    public function delete(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('Delete:ReceptionVisit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReceptionVisit');
    }

    public function restore(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('Restore:ReceptionVisit');
    }

    public function forceDelete(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('ForceDelete:ReceptionVisit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReceptionVisit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReceptionVisit');
    }

    public function replicate(AuthUser $authUser, ReceptionVisit $receptionVisit): bool
    {
        return $authUser->can('Replicate:ReceptionVisit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReceptionVisit');
    }

}