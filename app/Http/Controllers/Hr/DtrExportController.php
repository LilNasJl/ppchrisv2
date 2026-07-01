<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\Exports\DtrCsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DtrExportController extends Controller
{
    public function __invoke(Request $request, DtrCsvExportService $exporter): StreamedResponse
    {
        $this->authorizeHr($request);

        return $exporter->download(
            employeeId: $request->integer('employee_id'),
            branchId: $request->integer('branch_id'),
            periodId: $request->integer('period_id'),
        );
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
