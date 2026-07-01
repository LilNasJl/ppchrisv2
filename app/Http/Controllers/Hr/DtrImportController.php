<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\Imports\DtrImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DtrImportController extends Controller
{
    public function __invoke(Request $request, DtrImportService $importer): JsonResponse
    {
        $this->authorizeHr($request);

        $data = $request->validate([
            'import_name' => ['required', 'string', 'max:191'],
            'batch_id' => ['nullable', 'string', 'max:191'],
            'rows' => ['required', 'array', 'min:1', 'max:10000'],
            'rows.*' => ['array'],
        ]);

        return response()->json($importer->importRows(
            rows: $data['rows'],
            importName: $data['import_name'],
            fallbackBatchId: $data['batch_id'] ?? null,
        ));
    }

    protected function authorizeHr(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
    }
}
