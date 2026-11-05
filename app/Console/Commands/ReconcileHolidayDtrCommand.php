<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Services\AutomaticAbsenceService;
use App\Services\PhilippineHolidayService;
use App\Services\SaturdayWfhDtrService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileHolidayDtrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dtr:reconcile-holidays {--period= : Specific payroll period ID} {--branch= : Specific branch ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Philippine holidays, override holiday absences, and materialize Saturday WFH entries for payroll periods';

    /**
     * Execute the console command.
     */
    public function handle(
        AutomaticAbsenceService $absenceService,
        SaturdayWfhDtrService $wfhService,
        PhilippineHolidayService $holidayService
    ): int {
        $periodId = filled($this->option('period')) ? (int) $this->option('period') : null;
        $branchId = filled($this->option('branch')) ? (int) $this->option('branch') : null;

        $periods = $periodId
            ? PayrollPeriod::where('id', $periodId)->get()
            : PayrollPeriod::where('is_locked', false)->get();

        if ($periods->isEmpty()) {
            $this->warn('No active payroll period found to reconcile.');

            return self::SUCCESS;
        }

        $years = $periods
            ->flatMap(fn (PayrollPeriod $p): array => array_filter([
                $p->date_start ? Carbon::parse($p->date_start)->year : null,
                $p->date_end ? Carbon::parse($p->date_end)->year : null,
                now('Asia/Manila')->year,
            ]))
            ->unique()
            ->values();

        foreach ($years as $year) {
            $syncResult = $holidayService->syncHolidaysForYear((int) $year);
            $this->info("Synced {$syncResult['saved']} Philippine holidays for {$year} (merged {$syncResult['merged_duplicates']} duplicates).");
        }

        $totalHolidayReconciled = 0;
        $totalWfhCreated = 0;

        foreach ($periods as $period) {
            $totalHolidayReconciled += $absenceService->reconcileForScope($period->id, $branchId);

            $branches = $branchId ? [$branchId] : Branch::pluck('id')->all();
            foreach ($branches as $bId) {
                $totalWfhCreated += $wfhService->materializeScope($period->id, (int) $bId);
            }
        }

        $this->info("Reconciled {$totalHolidayReconciled} holiday D.T.R record(s) and materialized {$totalWfhCreated} Saturday WFH record(s).");

        return self::SUCCESS;
    }
}
