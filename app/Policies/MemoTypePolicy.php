<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MemoType;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemoTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MemoType');
    }

    public function view(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('View:MemoType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MemoType');
    }

    public function update(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('Update:MemoType');
    }

    public function delete(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('Delete:MemoType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MemoType');
    }

    public function restore(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('Restore:MemoType');
    }

    public function forceDelete(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('ForceDelete:MemoType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MemoType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MemoType');
    }

    public function replicate(AuthUser $authUser, MemoType $memoType): bool
    {
        return $authUser->can('Replicate:MemoType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MemoType');
    }

}