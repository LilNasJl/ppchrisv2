<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('dtr_change_requests')
            ->where('status', 'Pending')
            ->orderBy('id')
            ->chunkById(200, function ($requests): void {
                foreach ($requests as $item) {
                    DB::transaction(function () use ($item): void {
                        $request = DB::table('dtr_change_requests')->where('id', $item->id)->lockForUpdate()->first();
                        if (! $request || $request->status !== 'Pending') {
                            return;
                        }

                        $steps = json_decode((string) $request->workflow_snapshot, true);
                        if (! is_array($steps) || ! array_is_list($steps) || count($steps) !== (int) $request->total_levels
                            || (int) $request->current_level < 1 || (int) $request->current_level > (int) $request->total_levels) {
                            Log::warning('Pending DTR request needs manual HR approval route review.', ['request_id' => $request->id]);

                            return;
                        }

                        foreach ($steps as $step) {
                            if (! empty($step['is_hr']) || (empty($step['approver_employee_id']) && str_contains(strtolower((string) ($step['label'] ?? '')), 'hr'))) {
                                return;
                            }
                        }

                        $steps[] = [
                            'level' => count($steps) + 1,
                            'label' => 'HR Final Approval',
                            'approver_employee_id' => null,
                            'approver_name' => 'HR',
                            'is_hr' => true,
                        ];

                        DB::table('dtr_change_requests')->where('id', $request->id)->update([
                            'workflow_snapshot' => json_encode($steps, JSON_THROW_ON_ERROR),
                            'total_levels' => count($steps),
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        // An approval route may have advanced since this migration; do not remove its final step.
    }
};
