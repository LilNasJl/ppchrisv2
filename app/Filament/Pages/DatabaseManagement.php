<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\FullSystemRestoreService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnitEnum;

class DatabaseManagement extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.database-management';

    protected static ?string $title = 'Database Management';

    protected static ?string $navigationLabel = 'Database Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    public function mount(): void
    {
        if ($message = session()->pull('backup_error')) {
            Notification::make()->danger()->title('Backup could not be downloaded')
                ->body($message)->persistent()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadDatabaseBackup')
                    ->label('Database Backup (.sql)')
                    ->icon(Heroicon::CircleStack)
                    ->url(route('hr_tools.backup.database', ['format' => 'sql']))
                    ->openUrlInNewTab()
                    ->extraAttributes([
                        'data-navigate-ignore' => 'true',
                        'download' => '',
                    ]),

                Action::make('downloadDatabaseGzBackup')
                    ->label('Compressed Database Backup (.sql.gz)')
                    ->icon(Heroicon::ArchiveBox)
                    ->color('info')
                    ->url(route('hr_tools.backup.database', ['format' => 'gz']))
                    ->openUrlInNewTab()
                    ->extraAttributes([
                        'data-navigate-ignore' => 'true',
                        'download' => '',
                    ]),

                Action::make('downloadFullBackup')
                    ->label('Full System Backup (.zip)')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('primary')
                    ->url(route('hr_tools.backup.full'))
                    ->openUrlInNewTab()
                    ->extraAttributes([
                        'data-navigate-ignore' => 'true',
                        'download' => '',
                    ]),

                Action::make('restoreFullBackup')
                    ->label('Restore Full Backup')
                    ->icon(Heroicon::ArrowUpTray)
                    ->color('danger')
                    ->modalHeading('Restore Full System Backup')
                    ->modalSubmitActionLabel('Restore System')
                    ->schema([
                        FileUpload::make('backup_archive')
                            ->label('Full Backup ZIP')
                            ->disk('local')
                            ->directory('restore-uploads')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/zip',
                                'application/x-zip-compressed',
                                'application/octet-stream',
                            ])
                            ->maxSize(524288)
                            ->required(),
                        TextInput::make('backup_password')
                            ->label('Password for older encrypted backups (optional)')
                            ->password()
                            ->revealable()
                            ->autocomplete('off'),
                        TextInput::make('restore_confirmation')
                            ->label('Type RESTORE to continue')
                            ->required(),
                    ])
                    ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false)
                    ->action(fn (array $data): ?RedirectResponse => $this->restoreFullBackup($data)),
            ])
                ->label('Backup & Restore')
                ->icon(Heroicon::CircleStack)
                ->button(),
        ];
    }

    protected function restoreFullBackup(array $data): ?RedirectResponse
    {
        $this->authorizeCurrentUser(superAdminOnly: true);

        if (($data['restore_confirmation'] ?? null) !== 'RESTORE') {
            throw ValidationException::withMessages([
                'restore_confirmation' => 'Type RESTORE exactly to confirm the full-system restoration.',
            ]);
        }

        $storedPath = $data['backup_archive'] ?? null;

        if (is_array($storedPath)) {
            $storedPath = reset($storedPath);
        }

        if (! is_string($storedPath) || $storedPath === '') {
            throw ValidationException::withMessages([
                'backup_archive' => 'Select a full-system backup ZIP file.',
            ]);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($storedPath)) {
            throw ValidationException::withMessages([
                'backup_archive' => 'The uploaded backup file could not be found.',
            ]);
        }

        try {
            app(FullSystemRestoreService::class)->restore(
                $disk->path($storedPath),
                (string) ($data['backup_password'] ?? ''),
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('System restore failed')
                ->body($exception->getMessage())
                ->persistent()
                ->send();

            return null;
        } finally {
            $disk->delete($storedPath);
        }

        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->to(route('landing'))
            ->with('status', 'The full-system backup was restored successfully. Sign in using an account contained in the restored backup.');
    }

    protected function authorizeCurrentUser(bool $superAdminOnly = false): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && in_array($user->role, ['admin', 'hr'], true), 403);

        if ($superAdminOnly) {
            abort_unless($user->hasRole('super_admin'), 403);
        }

        return $user;
    }

    protected function getViewData(): array
    {
        return [
            'sqlDownloadUrl' => route('hr_tools.backup.database', ['format' => 'sql']),
            'sqlGzDownloadUrl' => route('hr_tools.backup.database', ['format' => 'gz']),
            'fullDownloadUrl' => route('hr_tools.backup.full'),
            'canRestore' => auth()->user()?->hasRole('super_admin') ?? false,
        ];
    }
}
