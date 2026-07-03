<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollSignatory;
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
        ]);

        $period = PayrollPeriod::query()->findOrFail($validated['period_id']);

        return view('payroll.print-summary', [
            'period' => $period,
            'headers' => $calculator->summaryHeaders(),
            'rows' => $calculator->branchSummaryRows($period),
            'signatory' => PayrollSignatory::default(),
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
