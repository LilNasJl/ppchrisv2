<?php

namespace App\Observers;

use App\Models\Dtr;
use App\Services\DtrDailyAggregationService;

class DtrDailyAggregationObserver
{
    public function created(Dtr $record): void
    {
        $this->recalculate($record);
    }

    public function updated(Dtr $record): void
    {
        if (! DtrDailyAggregationService::automaticRecalculationEnabled()) {
            return;
        }

        app(DtrDailyAggregationService::class)->recalculateChangedRecord($record);
    }

    public function deleted(Dtr $record): void
    {
        $this->recalculate($record);
    }

    public function restored(Dtr $record): void
    {
        $this->recalculate($record);
    }

    public function forceDeleted(Dtr $record): void
    {
        $this->recalculate($record);
    }

    protected function recalculate(Dtr $record): void
    {
        if (! DtrDailyAggregationService::automaticRecalculationEnabled()) {
            return;
        }

        app(DtrDailyAggregationService::class)->recalculateRecord($record);
    }
}
