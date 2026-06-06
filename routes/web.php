<?php

use App\Http\Controllers\Hr\DtrExportController;
use App\Http\Controllers\Hr\DtrImportController;
use App\Http\Controllers\Hr\EmployeeImportController;
use App\Models\Leave;
use App\Models\Memo;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/{livewirePrefix}/update', function () {
    $fallbackUrl = url('/hr');
    $referer = request()->headers->get('referer');
    $refererHost = is_string($referer) ? parse_url($referer, PHP_URL_HOST) : null;
    $refererPath = is_string($referer) ? parse_url($referer, PHP_URL_PATH) : null;

    if (
        is_string($referer) &&
        $refererHost === request()->getHost() &&
        ! preg_match('#^/livewire(?:-[A-Za-z0-9]+)?/update$#', (string) $refererPath)
    ) {
        return redirect()->to($referer);
    }

    return redirect()->to($fallbackUrl);
})
    ->where('livewirePrefix', 'livewire(?:-[A-Za-z0-9]+)?')
    ->name('livewire.update.get-fallback');

Route::get('/memo-attachments/{memo}', function (Memo $memo) {
    $user = request()->user();

    abort_unless($user, 403);
    abort_unless(in_array($user->role, ['hr', 'admin'], true) || (int) $user->employee?->id === (int) $memo->employee_id, 403);
    abort_if(blank($memo->attachment_path) || ! Storage::disk('local')->exists($memo->attachment_path), 404);

    return Storage::disk('local')->response($memo->attachment_path, $memo->attachment_name);
})
    ->middleware('auth')
    ->name('memo.attachments.show');

Route::get('/leave-attachments/{leave}', function (Leave $leave) {
    $user = request()->user();

    abort_unless($user, 403);
    abort_unless(in_array($user->role, ['hr', 'admin'], true) || (int) $user->employee?->id === (int) $leave->employee_id, 403);
    abort_if(blank($leave->attachment_path) || ! Storage::disk('local')->exists($leave->attachment_path), 404);

    return Storage::disk('local')->response($leave->attachment_path, $leave->attachment_name);
})
    ->withTrashed()
    ->middleware('auth')
    ->name('leave.attachments.show');

Route::get('/ticket-attachments/{ticket}/{source}', function (Ticket $ticket, string $source) {
    $user = request()->user();

    abort_unless($user, 403);
    abort_unless(in_array($source, ['employee', 'hr'], true), 404);
    abort_unless(in_array($user->role, ['hr', 'admin'], true) || (int) $user->employee?->id === (int) $ticket->employee_id, 403);

    $path = $source === 'hr'
        ? $ticket->hr_attachment_path
        : $ticket->employee_attachment_path;
    $name = $source === 'hr'
        ? $ticket->hr_attachment_name
        : $ticket->employee_attachment_name;

    abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404);

    return Storage::disk('local')->response($path, $name);
})
    ->middleware('auth')
    ->name('ticket.attachments.show');

Route::middleware('auth')
    ->prefix('hr-tools')
    ->name('hr_tools.')
    ->group(function (): void {
        Route::post('/import/employees', EmployeeImportController::class)->name('import.employees');
        Route::post('/import/dtr', DtrImportController::class)->name('import.dtr');
        Route::get('/export/dtr.csv', DtrExportController::class)->name('export.dtr');
    });

Route::redirect('/', '/hr');
