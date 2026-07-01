<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\Imports\EmployeeAccountImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeImportController extends Controller
{
    public function __invoke(Request $request, EmployeeAccountImportService $importer): JsonResponse
    {
        $this->authorizeHr($request);

        $data = $request->validate([
            'batch_id' => ['required', 'string', 'max:191'],
            'import_name' => ['required', 'string', 'max:191'],
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*' => ['array'],
        ]);

        return response()->json($importer->importRows(
            rows: $data['rows'],
            batchId: $data['batch_id'],
            importName: $data['import_name'],
        ));
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
