<?php

namespace App\Observers;

use App\Support\HrDatabaseNotification;
use Illuminate\Database\Eloquent\Model;

class HrActionNotificationObserver
{
    public function created(Model $record): void
    {
        HrDatabaseNotification::recordCreated($record);
    }

    public function updated(Model $record): void
    {
        HrDatabaseNotification::recordUpdated($record);
    }

    public function deleted(Model $record): void
    {
        HrDatabaseNotification::recordDeleted($record);
    }

    public function restored(Model $record): void
    {
        HrDatabaseNotification::recordRestored($record);
    }

    public function forceDeleted(Model $record): void
    {
        HrDatabaseNotification::recordForceDeleted($record);
    }
}
