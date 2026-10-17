<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollSignatory;
use App\Services\PayrollCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PayrollByBranchPrintController extends Controller
{
    public function __invoke(Request $request, PayrollCalculator $calculator): View
    {
        $this->authorizeHr($request);

        $validated = $request->validate([
            'period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            'scope' => ['nullable', 'in:branch,department,all'],
            'branch_id' => ['required_if:scope,branch', 'nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['required_if:scope,department', 'nullable', 'integer', 'exists:departments,id'],
        ]);

        $period = PayrollPeriod::query()->findOrFail($validated['period_id']);
        $scope = $validated['scope'] ?? 'branch';
        $branch = $scope === 'branch' ? Branch::query()->findOrFail($validated['branch_id']) : null;
        $department = $scope === 'department' ? Department::query()->findOrFail($validated['department_id']) : null;
        if ($branch) {
            abort_unless(array_key_exists($branch->id, $calculator->branchOptionsForPeriod($period)), 404);
        }
        $scopeId = $branch?->id ?: $department?->id;

        return view('payroll.print-by-branch', [
            'period' => $period,
            'branch' => $branch,
            'scopeName' => $branch?->branch_name ?: ($department?->name ?: 'All Branches'),
            'rows' => $calculator->rowsForScope($period, $scope, $scopeId),
            'signatory' => PayrollSignatory::default(),
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
