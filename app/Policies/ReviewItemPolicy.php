<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReviewItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReviewItem');
    }

    public function view(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('View:ReviewItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReviewItem');
    }

    public function update(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('Update:ReviewItem');
    }

    public function delete(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('Delete:ReviewItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReviewItem');
    }

    public function restore(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('Restore:ReviewItem');
    }

    public function forceDelete(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('ForceDelete:ReviewItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReviewItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReviewItem');
    }

    public function replicate(AuthUser $authUser, ReviewItem $reviewItem): bool
    {
        return $authUser->can('Replicate:ReviewItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReviewItem');
    }

}