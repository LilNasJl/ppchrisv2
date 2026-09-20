<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Leave;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LeavePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Leave');
    }

    public function view(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('View:Leave');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Leave');
    }

    public function update(AuthUser $authUser, Leave $leave): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, Leave $leave): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Leave $leave): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Leave $leave): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Leave $leave): bool
    {
        return $authUser->can('Replicate:Leave');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Leave');
    }
}
