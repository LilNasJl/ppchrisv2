<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FullSystemBackupService;
use App\Services\StreamedDatabaseBackupService;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DatabaseBackupDownloadController extends Controller
{
    public function database(Request $request, StreamedDatabaseBackupService $backup): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeDownload($request);

        $format = (string) $request->query('format', 'sql');

        try {
            return $backup->download($format);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failedDownload($exception->getMessage());
        }
    }

    public function full(Request $request, FullSystemBackupService $backup): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeDownload($request);

        try {
            return $backup->download();
        } catch (Throwable $exception) {
            report($exception);

            return $this->failedDownload($exception->getMessage());
        }
    }

    private function authorizeDownload(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && in_array($user->role, ['admin', 'hr'], true)
                && $user->canAccessPanel(Filament::getPanel('hr'))
                && ($user->hasRole('super_admin') || $user->can('View:DatabaseManagement') || $user->can('page_DatabaseManagement')),
            403,
        );
    }

    private function failedDownload(?string $reason = null): RedirectResponse
    {
        $message = 'The backup could not be prepared. Check the application log and server storage permissions.';
        if (config('app.debug') && filled($reason)) {
            $message .= ' ('.$reason.')';
        }

        return redirect('/hr/database-management')->with(
            'backup_error',
            $message,
        );
    }
}
