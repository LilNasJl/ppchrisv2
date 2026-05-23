<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HolidayType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class HolidayTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HolidayType');
    }

    public function view(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('View:HolidayType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HolidayType');
    }

    public function update(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('Update:HolidayType');
    }

    public function delete(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('Delete:HolidayType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HolidayType');
    }

    public function restore(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('Restore:HolidayType');
    }

    public function forceDelete(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('ForceDelete:HolidayType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HolidayType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HolidayType');
    }

    public function replicate(AuthUser $authUser, HolidayType $holidayType): bool
    {
        return $authUser->can('Replicate:HolidayType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HolidayType');
    }
}
