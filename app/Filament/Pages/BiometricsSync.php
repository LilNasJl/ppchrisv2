<?php

namespace App\Filament\Pages;

use App\Models\BiometricsSyncRequest;
use App\Models\PayrollPeriod;
use App\Services\Biometrics\BiometricsClient;
use App\Services\Biometrics\BiometricsSyncService;
use App\Services\Biometrics\PayrollSyncImporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Override;

class BiometricsSync extends Page
{
    protected string $view = 'filament.pages.biometrics-sync';

    protected static bool $shouldRegisterNavigation = false;

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    #[Url]
    public ?string $device = null;

    public ?string $periodId = null;

    public bool $showSyncModal = false;

    public bool $showEmployeeSyncModal = false;

    public ?string $detailSyncId = null;

    public array $archivedRowDetails = [];

    public bool $archiveLookupAttempted = false;

    public bool $archiveLookupFailed = false;

    public string $detailTab = 'overview';

    public string $employeeSearch = '';

    public array $exportDtrRows = [];

    public int $exportDtrPage = 1;

    public int $exportDtrTotal = 0;

    public bool $exportDtrShowAll = false;

    public bool $exportDtrLoaded = false;

    public bool $exportDtrFailed = false;

    public string $exportDtrSource = '';

    public string $exportDtrFailureMessage = '';

    public string $connectionError = '';

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['hr', 'admin'], true) && Dtr::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        if ($this->device !== null) {
            $this->authorizeSelectedDevice();
        }
    }

    public function devices(): array
    {
        abort_unless(static::canAccess(), 403);
        $this->connectionError = '';
        try {
            $devices = app(BiometricsClient::class)->cachedDevices();
            $permitted = $this->permittedDeviceIds();

            return $permitted === null ? $devices : array_values(array_filter(
                $devices,
                fn ($device): bool => in_array((string) ($device['id'] ?? ''), $permitted, true),
            ));
        } catch (\Throwable $e) {
            $this->connectionError = $e instanceof \RuntimeException && ! ($e instanceof RequestException) ? $e->getMessage() : 'Unable to reach BiometricsWeb. Check the connection settings.';

            return [];
        }
    }

    /** @return array<int, string>|null */
    protected function permittedDeviceIds(): ?array
    {
        return null;
    }

    protected function authorizeSelectedDevice(): void
    {
        $permitted = $this->permittedDeviceIds();
        abort_unless(static::canAccess() && $this->device !== null && $this->device !== ''
            && ($permitted === null || in_array($this->device, $permitted, true)), 403);
    }

    public function canSyncEmployees(): bool
    {
        return true;
    }

    public function returnUrl(): string
    {
        return Dtr::getUrl();
    }

    public function openSync(): void
    {
        $this->authorizeSelectedDevice();
        if ($this->active()) {
            throw ValidationException::withMessages(['device' => BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        $this->detailSyncId = null;
        $this->resetValidation();
        $this->periodId = (string) (PayrollPeriod::where('is_locked', false)->newestFirst()->value('id') ?? '');
        $this->showSyncModal = true;
    }

    public function submitSync(): void
    {
        $this->authorizeSelectedDevice();
        if ($this->active()) {
            throw ValidationException::withMessages(['device' => BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        $this->validate(['periodId' => 'required|integer|exists:payroll_periods,id']);
        try {
            app(BiometricsSyncService::class)->create($this->device, (int) $this->periodId, (int) auth()->id());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['periodId' => 'Unable to queue synchronization. Check BiometricsWeb connectivity and try again.']);
        }
        $this->showSyncModal = false;
        Notification::make()->title('Payroll synchronization queued')->body('You can leave this page. Processing continues in the background.')->success()->send();
    }

    public function openEmployeeSync(): void
    {
        $this->authorizeSelectedDevice();
        abort_unless($this->canSyncEmployees(), 403);
        if ($this->active()) {
            throw ValidationException::withMessages(['device' => BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        $this->detailSyncId = null;
        $this->resetValidation();
        $this->showEmployeeSyncModal = true;
    }

    public function submitEmployeeSync(): void
    {
        $this->authorizeSelectedDevice();
        abort_unless($this->canSyncEmployees(), 403);
        if ($this->active()) {
            throw ValidationException::withMessages(['device' => BiometricsSyncService::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        try {
            app(BiometricsSyncService::class)->createEmployee($this->device, (int) auth()->id());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['device' => 'Unable to queue employee synchronization. Check BiometricsWeb connectivity and try again.']);
        }
        $this->showEmployeeSyncModal = false;
        Notification::make()->title('Employee synchronization queued')->body('You can leave this page. Processing continues in the background.')->success()->send();
    }

    public function history()
    {
        $this->authorizeSelectedDevice();

        return $this->visibleHistoryQuery($this->device)
            ->with(['period', 'requestedBy.employee'])->latest()->limit(50)->get();
    }

    public function latestSyncForDevice(string $deviceId): ?BiometricsSyncRequest
    {
        abort_unless(static::canAccess(), 403);
        $permitted = $this->permittedDeviceIds();
        abort_unless($permitted === null || in_array($deviceId, $permitted, true), 403);

        return $this->visibleHistoryQuery($deviceId)
            ->with(['period', 'requestedBy.employee'])->latest()->first();
    }

    protected function visibleHistoryQuery(string $deviceId)
    {
        return BiometricsSyncRequest::query()->where('device_id', $deviceId)
            ->when(! $this->canSyncEmployees(), fn ($query) => $query
                ->where('sync_type', 'payroll')
                ->where('requested_by', auth()->id()));
    }

    public function openDetails(string $syncId): void
    {
        $this->authorizeSelectedDevice();
        abort_unless($this->visibleHistoryQuery($this->device)->whereKey($syncId)->exists(), 404);

        $this->showSyncModal = false;
        $this->showEmployeeSyncModal = false;
        $this->detailSyncId = $syncId;
        $this->archivedRowDetails = [];
        $this->archiveLookupAttempted = false;
        $this->archiveLookupFailed = false;
        $this->detailTab = 'overview';
        $this->employeeSearch = '';
        $this->resetExportDtrEntries();
    }

    public function closeDetails(): void
    {
        $this->detailSyncId = null;
        $this->archivedRowDetails = [];
        $this->archiveLookupAttempted = false;
        $this->archiveLookupFailed = false;
        $this->detailTab = 'overview';
        $this->employeeSearch = '';
        $this->resetExportDtrEntries();
    }

    public function selectDetailTab(string $tab): void
    {
        abort_unless(static::canAccess() && $this->detailSync(), 403);
        abort_unless(in_array($tab, ['overview', 'entries'], true), 422);
        $this->detailTab = $tab;
    }

    private function resetExportDtrEntries(): void
    {
        $this->exportDtrRows = [];
        $this->exportDtrPage = 1;
        $this->exportDtrTotal = 0;
        $this->exportDtrShowAll = false;
        $this->exportDtrLoaded = false;
        $this->exportDtrFailed = false;
        $this->exportDtrSource = '';
        $this->exportDtrFailureMessage = '';
    }

    public function loadExportDtrEntries(): void
    {
        abort_unless(static::canAccess() && $this->detailTab === 'entries', 403);
        $sync = $this->detailSync();
        abort_unless($sync, 404);
        if ($this->exportDtrLoaded) {
            return;
        }

        $export = ($sync->result ?? [])['export'] ?? null;
        $recordCount = is_array($export) ? (int) ($export['record_count'] ?? 0) : 0;
        $perPage = $this->exportDtrPageSize($recordCount);
        if (! is_array($export) || empty($export['sha256']) || $this->exportDtrPage < 1
            || $this->exportDtrPage > max(1, (int) ceil($recordCount / $perPage))) {
            $this->exportDtrFailed = true;
            $this->exportDtrFailureMessage = 'This sync has no complete export metadata to display.';

            return;
        }

        try {
            $binary = app(BiometricsClient::class)->request('GET', '/api/hris/sync/'.$sync->id.'/file')->body();
            $page = app(PayrollSyncImporter::class)->archivedRecordsPage($binary, $export, $this->exportDtrPage, $perPage, result: $sync->result ?? []);
            $this->exportDtrRows = $page['rows'];
            $this->exportDtrTotal = $page['total'];
            $this->exportDtrLoaded = true;
            $this->exportDtrFailed = false;
            $this->exportDtrSource = 'archive';
            $this->exportDtrFailureMessage = '';
        } catch (\Throwable $e) {
            $result = $sync->result ?? [];
            $saved = app(PayrollSyncImporter::class)->savedRejectedRecordsPage(
                $result['row_details'] ?? [],
                (int) ($export['record_count'] ?? 0),
                (int) ($result['pending'] ?? 0),
                $this->exportDtrPage,
                $perPage,
            );
            if ($saved !== null) {
                $this->exportDtrRows = $saved['rows'];
                $this->exportDtrTotal = $saved['total'];
                $this->exportDtrLoaded = true;
                $this->exportDtrSource = 'saved_rejected';
                $this->exportDtrFailed = false;

                return;
            }
            $this->exportDtrFailed = true;
            $this->exportDtrFailureMessage = $e instanceof RequestException && $e->response->status() === 409
                ? 'BiometricsWeb refused access to this completed export (HTTP 409). Deploy the archived-export access update on the live BiometricsWeb site, then retry.'
                : 'The original export could not be downloaded or verified. Check that the file still exists on BiometricsWeb, then retry.';
        }
    }

    public function changeExportDtrPage(int $page): void
    {
        abort_unless(static::canAccess() && $this->detailTab === 'entries' && $this->detailSync(), 403);
        abort_unless($page >= 1 && $page <= max(1, (int) ceil($this->exportDtrTotal / $this->exportDtrPageSize())), 422);
        $this->exportDtrPage = $page;
        $this->exportDtrRows = [];
        $this->exportDtrLoaded = false;
        $this->exportDtrFailed = false;
        $this->exportDtrSource = '';
        $this->exportDtrFailureMessage = '';
        $this->loadExportDtrEntries();
    }

    public function showAllExportDtrEntries(): void
    {
        abort_unless(static::canAccess() && $this->detailTab === 'entries' && $this->detailSync(), 403);
        $this->exportDtrShowAll = true;
        $this->exportDtrPage = 1;
        $this->exportDtrRows = [];
        $this->exportDtrLoaded = false;
        $this->exportDtrFailed = false;
        $this->exportDtrSource = '';
        $this->exportDtrFailureMessage = '';
        $this->loadExportDtrEntries();
    }

    public function showPagedExportDtrEntries(): void
    {
        abort_unless(static::canAccess() && $this->detailTab === 'entries' && $this->detailSync(), 403);
        $this->exportDtrShowAll = false;
        $this->exportDtrPage = 1;
        $this->exportDtrRows = [];
        $this->exportDtrLoaded = false;
        $this->exportDtrFailed = false;
        $this->exportDtrSource = '';
        $this->exportDtrFailureMessage = '';
        $this->loadExportDtrEntries();
    }

    private function exportDtrPageSize(?int $total = null): int
    {
        return $this->exportDtrShowAll ? max(1, $total ?? $this->exportDtrTotal) : 25;
    }

    public function retryExportDtrEntries(): void
    {
        $this->exportDtrLoaded = false;
        $this->exportDtrFailed = false;
        $this->exportDtrSource = '';
        $this->exportDtrFailureMessage = '';
        $this->loadExportDtrEntries();
    }

    public function detailSync(): ?BiometricsSyncRequest
    {
        if (! $this->detailSyncId || ! $this->device) {
            return null;
        }

        $this->authorizeSelectedDevice();

        return $this->visibleHistoryQuery($this->device)->whereKey($this->detailSyncId)
            ->with(['period', 'requestedBy.employee'])->first();
    }

    public function employeeRows(): array
    {
        $sync = $this->detailSync();
        if (! $sync || $sync->sync_type !== 'employee') {
            return [];
        }
        $rows = $sync->result['employees'] ?? [];
        $search = mb_strtolower(trim($this->employeeSearch));
        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, fn (array $employee) => str_contains(mb_strtolower(implode(' ', [
            $employee['id'], $employee['uid'], $employee['name'], $employee['type'], $employee['schedule'],
            implode(' ', $employee['fingerprint_ids'] ?? []), $employee['created'],
        ])), $search)));
    }

    public function loadArchivedRows(): void
    {
        $this->authorizeSelectedDevice();
        if ($this->archiveLookupAttempted) {
            return;
        }

        $sync = $this->detailSync();
        abort_unless($sync, 404);
        $this->archiveLookupAttempted = true;
        $result = $sync->result ?? [];
        $export = $result['export'] ?? null;
        if (! is_array($export) || empty($export['sha256'])) {
            $this->archiveLookupFailed = true;

            return;
        }

        try {
            $binary = app(BiometricsClient::class)->request('GET', '/api/hris/sync/'.$sync->id.'/file')->body();
            $this->archivedRowDetails = app(PayrollSyncImporter::class)->archivedRowDetails($binary, $export, $result);
        } catch (\Throwable $e) {
            $this->archiveLookupFailed = true;
        }
    }

    public function retryArchivedRows(): void
    {
        $this->archiveLookupAttempted = false;
        $this->archiveLookupFailed = false;
        $this->loadArchivedRows();
    }

    public function active(): bool
    {
        $this->authorizeSelectedDevice();

        return BiometricsSyncRequest::where('active_device_key', $this->device)->exists();
    }
}
