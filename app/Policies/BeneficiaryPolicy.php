<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Beneficiary;
use Illuminate\Auth\Access\HandlesAuthorization;

class BeneficiaryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Beneficiary');
    }

    public function view(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('View:Beneficiary');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Beneficiary');
    }

    public function update(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('Update:Beneficiary');
    }

    public function delete(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('Delete:Beneficiary');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Beneficiary');
    }

    public function restore(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('Restore:Beneficiary');
    }

    public function forceDelete(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('ForceDelete:Beneficiary');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Beneficiary');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Beneficiary');
    }

    public function replicate(AuthUser $authUser, Beneficiary $beneficiary): bool
    {
        return $authUser->can('Replicate:Beneficiary');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Beneficiary');
    }

}