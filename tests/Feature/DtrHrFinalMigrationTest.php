<?php

namespace Tests\Feature;

use App\Models\DtrChangeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DtrHrFinalMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_legacy_routes_gain_hr_without_rewriting_history_or_completed_requests(): void
    {
        $legacy = $this->requestId('Pending', [
            ['level' => 1, 'label' => 'SIC', 'approver_employee_id' => 11, 'approver_name' => 'SIC'],
            ['level' => 2, 'label' => 'RC', 'approver_employee_id' => 12, 'approver_name' => 'RC'],
        ], 2, [['level' => 1, 'action' => 'Approved', 'actor_name' => 'SIC']]);
        $alreadyHr = $this->requestId('Pending', [
            ['level' => 1, 'label' => 'SIC', 'approver_employee_id' => 11, 'approver_name' => 'SIC'],
            ['level' => 2, 'label' => 'HR Final Approval', 'approver_employee_id' => null, 'approver_name' => 'HR', 'is_hr' => true],
        ], 2, [['level' => 1, 'action' => 'Approved', 'actor_name' => 'SIC']]);
        $completed = $this->requestId('Approved', [
            ['level' => 1, 'label' => 'SIC', 'approver_employee_id' => 11, 'approver_name' => 'SIC'],
        ], 1, [['level' => 1, 'action' => 'Approved', 'actor_name' => 'SIC']]);

        $migration = require database_path('migrations/2026_10_28_000001_append_hr_to_pending_dtr_approvals.php');
        $migration->up();
        $migration->up();

        $request = DtrChangeRequest::query()->findOrFail($legacy);
        $this->assertSame(3, $request->total_levels);
        $this->assertSame(2, $request->current_level);
        $this->assertSame('RC', $request->current_step_label);
        $this->assertSame('SIC', $request->approvals_history[0]['actor_name']);
        $this->assertTrue($request->workflow_snapshot[2]['is_hr']);
        $this->assertSame(2, DtrChangeRequest::query()->findOrFail($alreadyHr)->total_levels);
        $this->assertSame(1, DtrChangeRequest::query()->findOrFail($completed)->total_levels);
    }

    private function requestId(string $status, array $steps, int $currentLevel, array $history): int
    {
        return DB::table('dtr_change_requests')->insertGetId([
            'employee_name_snapshot' => 'Test Employee',
            'branch_name_snapshot' => 'Test Branch',
            'payroll_period_title_snapshot' => 'Test Period',
            'date_from' => '2026-10-01',
            'date_to' => '2026-10-01',
            'request_type' => DtrChangeRequest::TYPE_OTHERS,
            'description' => 'Test request',
            'status' => $status,
            'current_level' => $currentLevel,
            'total_levels' => count($steps),
            'current_step_label' => $steps[$currentLevel - 1]['label'],
            'workflow_snapshot' => json_encode($steps, JSON_THROW_ON_ERROR),
            'approvals_history' => json_encode($history, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
