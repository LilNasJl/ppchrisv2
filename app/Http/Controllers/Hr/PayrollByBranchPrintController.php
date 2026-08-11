<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
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
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $period = PayrollPeriod::query()->findOrFail($validated['period_id']);
        $branch = Branch::query()->findOrFail($validated['branch_id']);
        $branchOptions = $calculator->branchOptionsForPeriod($period);

        abort_unless(array_key_exists($branch->id, $branchOptions), 404);

        return view('payroll.print-by-branch', [
            'period' => $period,
            'branch' => $branch,
            'rows' => $calculator->rows($period, $branch->id),
            'signatory' => PayrollSignatory::default(),
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
