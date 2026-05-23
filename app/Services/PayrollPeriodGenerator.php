<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PayrollPeriodGenerator
{
    public function ensureCurrentPeriod(?CarbonInterface $date = null): array
    {
        $data = $this->currentPeriodData($date);

        return DB::transaction(function () use ($data): array {
            $period = PayrollPeriod::withTrashed()
                ->whereDate('date_start', $data['date_start'])
                ->whereDate('date_end', $data['date_end'])
                ->first();

            if ($period) {
                if ($period->trashed()) {
                    $period->restore();
                }

                if ($period->title !== $data['title']) {
                    $period->forceFill(['title' => $data['title']])->save();
                }

                return [
                    'period' => $period,
                    'created' => false,
                    'data' => $data,
                ];
            }

            $period = PayrollPeriod::create([
                'title' => $data['title'],
                'date_start' => $data['date_start'],
                'date_end' => $data['date_end'],
                'date_payout' => $data['date_payout'],
                'description' => 'Payroll currently running',
                'is_locked' => false,
            ]);

            return [
                'period' => $period,
                'created' => true,
                'data' => $data,
            ];
        });
    }

    public function currentPeriodData(?CarbonInterface $date = null): array
    {
        $now = $date
            ? CarbonImmutable::instance($date)->setTimezone('Asia/Manila')
            : CarbonImmutable::now('Asia/Manila');

        $day = (int) $now->format('j');

        if ($day >= 26 || $day <= 10) {
            $start = $day <= 10
                ? $now->subMonthNoOverflow()->day(26)
                : $now->day(26);

            $endBase = $day >= 26
                ? $now->addMonthNoOverflow()
                : $now;

            $end = $endBase->day(10);
            $payout = $this->adjustForWeekend($endBase->day(15));
        } else {
            $start = $now->day(11);
            $end = $now->day(25);
            $payout = $this->adjustForWeekend($now->endOfMonth()->startOfDay());
        }

        return [
            'title' => $this->periodTitle($start, $end),
            'period' => $end->format('n').','.$payout->format('Y'),
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
            'date_payout' => $payout->toDateString(),
        ];
    }

    private function adjustForWeekend(CarbonImmutable $date): CarbonImmutable
    {
        return match ((int) $date->format('w')) {
            6 => $date->subDay(),
            0 => $date->subDays(2),
            default => $date,
        };
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
}
