<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            'SHORTAGES',
            'COMPANY UNIFORM',
            'SSS LOAN',
            'SSS EE',
            'HDMF LOAN',
            'HDMF EE',
            'PHIC EE',
        ] as $title) {
            DB::table('deductions')->updateOrInsert(
                ['title' => $title],
                [
                    'description' => $title,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('deductions')
            ->whereIn('title', [
                'SHORTAGES',
                'COMPANY UNIFORM',
                'SSS LOAN',
                'SSS EE',
                'HDMF LOAN',
                'HDMF EE',
                'PHIC EE',
            ])
            ->delete();
    }
};
