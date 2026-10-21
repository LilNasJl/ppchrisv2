<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saturday_wfh_policy_periods', function (Blueprint $table): void {
            $table->id();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['effective_from', 'effective_to']);
        });

        $existing = DB::table('saturday_wfh_policies')->where('id', 1)->first();
        if ($existing) {
            DB::table('saturday_wfh_policy_periods')->insert([
                'effective_from' => $existing->effective_from,
                'effective_to' => null,
                'stopped_at' => null,
                'changed_by' => $existing->changed_by,
                'created_at' => $existing->created_at,
                'updated_at' => $existing->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saturday_wfh_policy_periods');
    }
};
