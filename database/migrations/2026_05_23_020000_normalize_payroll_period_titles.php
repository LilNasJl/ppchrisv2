<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payroll_periods')
            ->orderBy('id')
            ->get(['id', 'date_start', 'date_end'])
            ->each(function (object $period): void {
                $start = CarbonImmutable::parse($period->date_start, 'Asia/Manila');
                $end = CarbonImmutable::parse($period->date_end, 'Asia/Manila');

                DB::table('payroll_periods')
                    ->where('id', $period->id)
                    ->update(['title' => $this->periodTitle($start, $end)]);
            });
    }

    public function down(): void
    {
        //
    }

    private function periodTitle(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->isSameYear($end)) {
            if ($start->isSameMonth($end)) {
                return $start->format('M j').' - '.$end->format('j, Y');
            }

            return $start->format('M j').' - '.$end->format('M j, Y');
        }

        return $start->format('M j, Y').' - '.$end->format('M j, Y');
    }
};
