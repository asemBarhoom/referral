<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ServiceRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServiceRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ServiceRecord');
    }

    public function view(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('View:ServiceRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ServiceRecord');
    }

    public function update(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('Update:ServiceRecord');
    }

    public function delete(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('Delete:ServiceRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ServiceRecord');
    }

    public function restore(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('Restore:ServiceRecord');
    }

    public function forceDelete(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('ForceDelete:ServiceRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ServiceRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ServiceRecord');
    }

    public function replicate(AuthUser $authUser, ServiceRecord $serviceRecord): bool
    {
        return $authUser->can('Replicate:ServiceRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ServiceRecord');
    }

}