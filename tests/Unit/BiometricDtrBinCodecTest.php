<?php

namespace Tests\Unit;

use App\Services\Biometrics\BiometricDtrBinCodec;
use PHPUnit\Framework\TestCase;

class BiometricDtrBinCodecTest extends TestCase
{
    public function test_it_round_trips_biometric_dtr_records(): void
    {
        $records = [
            [
                'uid' => 1234,
                'name' => 'Dela Cruz, Juan',
                'date_in' => '2026-08-20',
                'time_in' => '08:00:00',
                'date_out' => '2026-08-20',
                'time_out' => '18:00:00',
                'sched' => 'Regular',
                'sched_start' => '08:00:00',
                'sched_end' => '18:00:00',
                'session_id' => 'session-1',
                'hris_transfer_format' => 'ppchris-sicrc-dtr',
                'hris_transfer_version' => 1,
                'early_overtime_minutes' => 30,
                'overtime_minutes' => 60,
                'early_overtime_status' => 'Approved',
                'after_overtime_status' => 'Approved',
                'credited_early_overtime_minutes' => 30,
                'credited_overtime_minutes' => 60,
            ],
            [
                'uid' => 5678,
                'name' => 'Maraña, Lisbos. Ivy Rose',
                'date_in' => '2026-08-20',
                'time_in' => '07:59:00',
                'date_out' => '',
                'time_out' => '',
                'sched' => 'Forgot to Punch',
                'sched_start' => '',
                'sched_end' => '',
                'session_id' => 'session-2',
            ],
        ];

        $codec = new BiometricDtrBinCodec;

        $this->assertSame($records, $codec->decode($codec->encode($records)));
    }

    public function test_it_rejects_a_truncated_bin_record(): void
    {
        $this->expectExceptionMessage('truncated record');

        (new BiometricDtrBinCodec)->decode(pack('n', 10).'short');
    }
}
