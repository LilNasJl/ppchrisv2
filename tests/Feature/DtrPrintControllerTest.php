<?php

use App\Http\Controllers\DtrPrintController;
use App\Models\Dtr;

test('forgot to punch rows keep available punch times in the printed DTR', function (): void {
    $controller = new class extends DtrPrintController
    {
        /**
         * @return array<string, string>
         */
        public function row(Dtr $record): array
        {
            return $this->printRow($record);
        }
    };

    $row = $controller->row(new Dtr([
        'date_in' => '2026-07-29',
        'time_in' => '05:54:00',
        'date_out' => null,
        'time_out' => null,
        'schedule_type' => 'Forgot to Punch',
        'day_part' => 'whole_day',
        'is_absent' => true,
    ]));

    expect($row)->toMatchArray([
        'date_in' => 'Jul 29, 2026',
        'time_in' => '05:54 AM',
        'date_out' => '-',
        'time_out' => '-',
        'schedule' => 'Forgot to Punch',
        'status' => 'Forgot to Punch',
    ]);
});

test('printed DTR rows expose normalized schedule labels', function (string $storedType, string $expectedLabel): void {
    $controller = new class extends DtrPrintController
    {
        /**
         * @return array<string, string>
         */
        public function row(Dtr $record): array
        {
            return $this->printRow($record);
        }
    };

    $row = $controller->row(new Dtr([
        'date_in' => '2026-07-30',
        'time_in' => '08:00:00',
        'date_out' => '2026-07-30',
        'time_out' => '18:00:00',
        'schedule_type' => $storedType,
        'day_part' => 'whole_day',
        'is_absent' => false,
    ]));

    expect($row['schedule'])->toBe($expectedLabel);
})->with([
    'regular' => ['Regular', 'Regular'],
    'first shift' => ['Shift1', 'Shift1'],
    'third shift' => ['Shift3', 'Shift3'],
    'first broken shift abbreviation' => ['Brkn1', 'Broken1'],
    'second broken shift label' => ['Broken Shift 2', 'Broken2'],
]);

test('actual absence rows continue hiding punch values in the printed DTR', function (): void {
    $controller = new class extends DtrPrintController
    {
        /**
         * @return array<string, string>
         */
        public function row(Dtr $record): array
        {
            return $this->printRow($record);
        }
    };

    $row = $controller->row(new Dtr([
        'date_in' => '2026-07-30',
        'time_in' => '08:00:00',
        'schedule_type' => 'Absent',
        'day_part' => 'whole_day',
        'is_absent' => true,
    ]));

    expect($row['time_in'])->toBe('-')
        ->and($row['status'])->toBe('Absent');
});
