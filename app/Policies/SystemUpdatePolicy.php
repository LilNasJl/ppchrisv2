<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SystemUpdate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SystemUpdatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SystemUpdate');
    }

    public function view(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('View:SystemUpdate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SystemUpdate');
    }

    public function update(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('Update:SystemUpdate');
    }

    public function delete(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('Delete:SystemUpdate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SystemUpdate');
    }

    public function restore(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('Restore:SystemUpdate');
    }

    public function forceDelete(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('ForceDelete:SystemUpdate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SystemUpdate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SystemUpdate');
    }

    public function replicate(AuthUser $authUser, SystemUpdate $systemUpdate): bool
    {
        return $authUser->can('Replicate:SystemUpdate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SystemUpdate');
    }
}
