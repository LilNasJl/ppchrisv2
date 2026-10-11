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
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
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
        $report->employee_id = isset($validated['employee_id'])
            ? (string) $validated['employee_id']
            : null;
        $report->date_from = $validated['date_from'] ?? null;
        $report->date_to = $validated['date_to'] ?? null;
        $report->month = $validated['month'] ?? null;

        $headers = $report->getReportHeadersProperty();

        return view('reports.print', [
            'title' => $report->getReportTitleProperty(),
            'headers' => $headers,
            'rows' => $report->downloadRows(),
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
