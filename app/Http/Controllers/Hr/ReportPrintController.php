<?php

namespace App\Http\Controllers\Hr;

use App\Filament\Pages\Reports;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportPrintController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorizeHr($request);

        $validated = $request->validate([
            'report_type' => ['required', 'string', 'max:100'],
            'payroll_period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'branch_id' => ['nullable', 'string', 'max:50'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $report = app(Reports::class);
        $reportType = (string) $validated['report_type'];

        abort_unless($report->supportsReportType($reportType), 404);

        $branchId = $validated['branch_id'] ?? 'all';

        if ($branchId !== 'all') {
            abort_unless(ctype_digit($branchId) && Branch::query()->whereKey((int) $branchId)->exists(), 422);
        }

        $report->report_type = $reportType;
        $report->payroll_period_id = isset($validated['payroll_period_id'])
            ? (string) $validated['payroll_period_id']
            : null;
        $report->branch_id = $branchId;
        $report->month = $validated['month'] ?? null;

        $headers = $report->getReportHeadersProperty();

        return view('reports.print', [
            'title' => $report->getReportTitleProperty(),
            'headers' => $headers,
            'rows' => $report->getReportRowsProperty(),
            'filters' => $report->getReportFilterLabelsProperty(),
            'isLandscape' => count($headers) > 6,
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
