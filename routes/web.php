<?php

use App\Http\Controllers\DtrPrintController;
use App\Http\Controllers\Hr\DtrExportController;
use App\Http\Controllers\Hr\DtrImportController;
use App\Http\Controllers\Hr\DtrSubmissionDownloadController;
use App\Http\Controllers\Hr\EmployeeImportController;
use App\Http\Controllers\Hr\PayrollByBranchPrintController;
use App\Http\Controllers\Hr\PayrollSummaryPrintController;
use App\Http\Controllers\Hr\ReportPrintController;
use App\Http\Controllers\Hr\ThirteenthMonthPayPrintController;
use App\Http\Controllers\PayrollPayslipPrintController;
use App\Http\Controllers\SicRc\DtrPreviewImportController;
use App\Http\Controllers\SicRc\EmployeeVisibleDtrExportController;
use App\Http\Controllers\SicRc\EmployeeVisibleDtrPrintController;
use App\Models\Leave;
use App\Models\Memo;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

Route::get('/profile-photos/{filename}', function (string $filename) {
    abort_unless(preg_match('/\A[A-Za-z0-9._-]+\z/', $filename) === 1, 404);

    $path = 'profile-photos/'.$filename;

    abort_unless(Storage::disk('public')->exists($path), 404);

    $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

    abort_unless(Str::startsWith($mimeType, 'image/'), 404);

    return response(Storage::disk('public')->get($path), 200, [
        'Cache-Control' => 'public, max-age=604800',
        'Content-Type' => $mimeType,
    ]);
})
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('profile_photos.show');

Route::get('/hr-tools/reports/print', ReportPrintController::class)
    ->name('hr_tools.reports.print');

Route::get('/hr-tools/payroll-summary/print', PayrollSummaryPrintController::class)
    ->name('hr_tools.payroll_summary.print');

Route::get('/hr-tools/payroll-by-branch/print', PayrollByBranchPrintController::class)
    ->name('hr_tools.payroll_by_branch.print');

Route::get('/hr-tools/13th-month-pay/print', ThirteenthMonthPayPrintController::class)
    ->name('hr_tools.thirteenth_month.print');

Route::get('/payroll/payslip/{period}/{employee?}', PayrollPayslipPrintController::class)
    ->name('payroll.payslip.print');

Route::get('/dtr/print/{period}/{branch}/{employee}', DtrPrintController::class)
    ->name('dtr.print');

Route::middleware('auth')
    ->prefix('hr-tools')
    ->name('hr_tools.')
    ->group(function (): void {
        Route::post('/import/employees', EmployeeImportController::class)->name('import.employees');
        Route::post('/import/dtr', DtrImportController::class)->name('import.dtr');
        Route::get('/export/dtr.csv', DtrExportController::class)->name('export.dtr');
        Route::get('/dtr-submissions/{submission}/download', DtrSubmissionDownloadController::class)->name('dtr_submissions.download');
        Route::get('/dtr-submissions/{submission}/view', [DtrSubmissionDownloadController::class, 'view'])->name('dtr_submissions.view');
    });

Route::middleware('auth:sicrc')
    ->prefix('sicrc-tools')
    ->name('sicrc_tools.')
    ->group(function (): void {
        Route::post('/import/dtr-preview', DtrPreviewImportController::class)->name('import.dtr_preview');
        Route::get('/export/dtr-preview.bin', EmployeeVisibleDtrExportController::class)->name('export.dtr_preview');
        Route::get('/dtr/print/{period}/{branch}/{employee}', EmployeeVisibleDtrPrintController::class)->name('dtr.print');
    });

Route::view('/', 'landing')->name('landing');
