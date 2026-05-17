<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['type' => 'Regular Holiday', 'rate' => 200, 'description' => 'Default regular holiday rate.'],
            ['type' => 'Special Holiday', 'rate' => 30, 'description' => 'Default special holiday premium rate.'],
        ] as $holidayType) {
            DB::table('holiday_types')->updateOrInsert(
                ['type' => $holidayType['type']],
                [
                    'rate' => $holidayType['rate'],
                    'description' => $holidayType['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('holiday_types')
            ->whereIn('type', ['Regular Holiday', 'Special Holiday'])
            ->delete();
    }
};
