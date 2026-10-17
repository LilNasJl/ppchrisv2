<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollSignatory;
use App\Models\PayrollSummaryScope;
use App\Services\PayrollCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PayrollSummaryPrintController extends Controller
{
    public function __invoke(Request $request, PayrollCalculator $calculator): View
    {
        $this->authorizeHr($request);

        $validated = $request->validate([
            'period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer'],
        ]);

        $period = PayrollPeriod::query()->findOrFail($validated['period_id']);
        $scope = PayrollSummaryScope::query()->where('payroll_period_id', $period->id)->first();
        $groupBy = $scope?->group_by ?: 'branch';

        $branchIds = array_key_exists('branch_ids', $validated)
            ? array_values(array_unique(array_filter(array_map('intval', $validated['branch_ids'] ?? []))))
            : ($scope ? $scope->resolvedBranchIds() : []);

        $departmentIds = array_key_exists('department_ids', $validated)
            ? array_values(array_unique(array_filter(array_map('intval', $validated['department_ids'] ?? []))))
            : ($scope ? $scope->resolvedDepartmentIds() : []);

        return view('payroll.print-summary', [
            'period' => $period,
            'headers' => $calculator->summaryHeaders($groupBy, $branchIds, $departmentIds),
            'rows' => $calculator->summaryRows(
                $period,
                $groupBy,
                $scope?->included_ids ?: [],
                $branchIds,
                $departmentIds,
            ),
            'signatory' => PayrollSignatory::default(),
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
