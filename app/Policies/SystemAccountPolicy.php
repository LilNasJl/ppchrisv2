<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SystemAccount');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:SystemAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SystemAccount');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:SystemAccount');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:SystemAccount');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SystemAccount');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:SystemAccount');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:SystemAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SystemAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SystemAccount');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:SystemAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SystemAccount');
    }

}