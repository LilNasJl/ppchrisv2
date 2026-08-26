<?php

namespace App\Http\Controllers\SicRc;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Models\SicRcDtrImport;
use App\Services\Imports\EmployeeVisibleDtrImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DtrPreviewImportController extends Controller
{
    public function __invoke(Request $request, EmployeeVisibleDtrImportService $importer): JsonResponse
    {
        $account = auth('sicrc')->user();

        abort_unless($account instanceof SicRcAccount, 403);

        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*' => ['array'],
            'import_name' => ['required', 'string', 'max:191'],
            'batch_id' => ['nullable', 'string', 'max:191'],
            'period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        abort_unless(in_array((int) $validated['branch_id'], $account->assignedBranchIds(), true), 403);

        $period = PayrollPeriod::query()->findOrFail($validated['period_id']);

        if ($period->is_locked) {
            $result = [
                'total' => count($validated['rows']),
                'successful' => 0,
                'failed' => count($validated['rows']),
                'skipped' => 0,
                'batch_id' => $validated['batch_id'] ?? null,
                'message' => 'The selected payroll period is locked and cannot accept SIC/RC preview D.T.R imports.',
                'errors' => [
                    ['row' => 0, 'message' => 'Selected payroll period is locked.'],
                ],
            ];

            $this->recordImport($account, $validated, $result);

            return response()->json($result, 422);
        }

        $rows = array_map(function (array $row) use ($validated): array {
            $row['payroll_period_id'] = $validated['period_id'];
            $row['Period ID'] = $validated['period_id'];
            $row['branch_id'] = $validated['branch_id'];
            $row['Branch ID'] = $validated['branch_id'];

            return $row;
        }, $validated['rows']);

        $result = $importer->importRows($rows, $validated['import_name'], $validated['batch_id'] ?? null);

        $this->recordImport($account, $validated, $result);

        return response()->json($result);
    }

    protected function recordImport(SicRcAccount $account, array $validated, array $result): void
    {
        $firstRow = collect($validated['rows'])->first(fn (mixed $row): bool => is_array($row)) ?: [];
        $successful = (int) ($result['successful'] ?? 0);
        $failed = (int) ($result['failed'] ?? 0);

        SicRcDtrImport::query()->create([
            'sic_rc_account_id' => $account->getKey(),
            'branch_id' => (int) $validated['branch_id'],
            'payroll_period_id' => (int) $validated['period_id'],
            'batch_id' => (string) ($result['batch_id'] ?? $validated['batch_id'] ?? ''),
            'import_name' => (string) $validated['import_name'],
            'source_filename' => filled($firstRow['source_filename'] ?? null)
                ? (string) $firstRow['source_filename']
                : null,
            'source_file_hash' => filled($firstRow['source_file_hash'] ?? null)
                ? (string) $firstRow['source_file_hash']
                : null,
            'total_rows' => (int) ($result['total'] ?? count($validated['rows'])),
            'imported_rows' => $successful,
            'skipped_rows' => (int) ($result['skipped'] ?? 0),
            'failed_rows' => $failed,
            'status' => match (true) {
                $failed > 0 => SicRcDtrImport::STATUS_FAILED,
                $successful > 0 => SicRcDtrImport::STATUS_COMPLETED,
                default => SicRcDtrImport::STATUS_NO_CHANGES,
            },
            'message' => $result['message'] ?? null,
            'errors' => $result['errors'] ?? [],
            'imported_at' => now(),
        ]);
    }
}
