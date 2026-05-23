<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Deduction;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DeductionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Deduction');
    }

    public function view(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('View:Deduction');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Deduction');
    }

    public function update(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('Update:Deduction');
    }

    public function delete(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('Delete:Deduction');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Deduction');
    }

    public function restore(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('Restore:Deduction');
    }

    public function forceDelete(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('ForceDelete:Deduction');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Deduction');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Deduction');
    }

    public function replicate(AuthUser $authUser, Deduction $deduction): bool
    {
        return $authUser->can('Replicate:Deduction');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Deduction');
    }
}
