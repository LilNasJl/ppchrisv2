<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollSignatory;
use App\Services\ThirteenthMonthPayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThirteenthMonthPayPrintController extends Controller
{
    public function __invoke(Request $request, ThirteenthMonthPayService $service): View
    {
        $this->authorizeHr($request);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'type' => ['required', Rule::in(array_keys($service->segmentOptions()))],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'view' => ['required', Rule::in(['details', 'summary'])],
        ]);

        $rows = $service->rows(
            (int) $validated['year'],
            $validated['type'],
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
        );

        return view('thirteenth-month.print', [
            'year' => (int) $validated['year'],
            'type' => $validated['type'],
            'viewMode' => $validated['view'],
            'rows' => $rows,
            'summaryRows' => $service->summaryRows($rows),
            'periodColumns' => $service->periodColumns((int) $validated['year'], $validated['type']),
            'segmentLabel' => $service->segmentLabel($validated['type']),
            'periodLabel' => $service->periodLabel((int) $validated['year'], $validated['type']),
            'divisor' => $service->divisor($validated['type']),
            'signatory' => PayrollSignatory::default(),
        ]);
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
