<?php

namespace App\Services\Biometrics;

use App\Jobs\ProcessBiometricsSync;
use App\Models\BiometricsSyncRequest;
use App\Models\PayrollPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BiometricsSyncService
{
    public const DEVICE_UNAVAILABLE_MESSAGE = 'Device unavailable. Another sync command is in progress. Please wait until it finishes.';

    public function create(string $deviceId, int $periodId, int $userId): BiometricsSyncRequest
    {
        $device = collect(app(BiometricsClient::class)->devices())->first(fn ($d) => (string) $d['id'] === $deviceId);
        if (! $device || ! $device['online']) {
            throw ValidationException::withMessages(['device' => 'Select an online biometrics device.']);
        }
        try {
            $sync = DB::transaction(function () use ($device, $deviceId, $periodId, $userId) {
                $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($periodId);
                if ($period->is_locked) {
                    throw ValidationException::withMessages(['periodId' => 'The payroll period is locked.']);
                }
                if ($period->date_start->diffInDays($period->date_end) > 31) {
                    throw ValidationException::withMessages(['periodId' => 'The device supports payroll ranges of at most 32 days.']);
                }
                if (BiometricsSyncRequest::where('active_device_key', $deviceId)->exists()) {
                    throw ValidationException::withMessages(['device' => self::DEVICE_UNAVAILABLE_MESSAGE]);
                }

                return BiometricsSyncRequest::create(['id' => (string) Str::uuid(), 'device_id' => $deviceId, 'device_name' => $device['name'],
                    'active_device_key' => $deviceId, 'requested_by' => $userId, 'sync_type' => 'payroll', 'payroll_period_id' => $period->id,
                    'date_from' => $period->date_start, 'date_to' => $period->date_end, 'status' => 'pending',
                    'message' => 'Waiting for the HRIS background worker to send the device command.',
                    'deadline_at' => now()->addMinutes(15)]);
            });
        } catch (QueryException $e) {
            if (! $this->isActiveDeviceConflict($e)) {
                throw $e;
            }

            throw ValidationException::withMessages(['device' => self::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        ProcessBiometricsSync::dispatch($sync->id)->afterCommit();

        return $sync;
    }

    public function createEmployee(string $deviceId, int $userId): BiometricsSyncRequest
    {
        $device = collect(app(BiometricsClient::class)->devices())->first(fn ($item) => (string) $item['id'] === $deviceId);
        if (! $device || ! $device['online']) {
            throw ValidationException::withMessages(['device' => 'Select an online biometrics device.']);
        }

        try {
            $sync = DB::transaction(function () use ($device, $deviceId, $userId): BiometricsSyncRequest {
                if (BiometricsSyncRequest::where('active_device_key', $deviceId)->exists()) {
                    throw ValidationException::withMessages(['device' => self::DEVICE_UNAVAILABLE_MESSAGE]);
                }

                return BiometricsSyncRequest::create([
                    'id' => (string) Str::uuid(), 'device_id' => $deviceId, 'device_name' => $device['name'],
                    'active_device_key' => $deviceId, 'requested_by' => $userId, 'sync_type' => 'employee',
                    'status' => 'pending', 'message' => 'Waiting for the HRIS background worker to send the employee command.',
                    'deadline_at' => now()->addMinutes(15),
                ]);
            });
        } catch (QueryException $e) {
            if (! $this->isActiveDeviceConflict($e)) {
                throw $e;
            }
            throw ValidationException::withMessages(['device' => self::DEVICE_UNAVAILABLE_MESSAGE]);
        }
        ProcessBiometricsSync::dispatch($sync->id)->afterCommit();

        return $sync;
    }

    private function isActiveDeviceConflict(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains(strtolower($exception->getMessage()), 'active_device_key');
    }

    public function advance(string $id): void
    {
        Cache::lock('biometrics-sync:'.$id, 300)->get(function () use ($id): void {
            $sync = BiometricsSyncRequest::findOrFail($id);
            try {
                $employee = $sync->sync_type === 'employee';
                if ($sync->terminal()) {
                    $this->acknowledge($sync);

                    return;
                }
                if ($sync->deadline_at->isPast()) {
                    $this->fail($sync, 'Synchronization exceeded its 15-minute deadline.');
                    $this->acknowledge($sync);

                    return;
                }
                $client = app(BiometricsClient::class);
                if (! $sync->command_id) {
                    $payload = $employee ? ['request_id' => $sync->id, 'sync_type' => 'employee'] : [
                        'request_id' => $sync->id, 'date_from' => $sync->date_from->toDateString(), 'date_to' => $sync->date_to->toDateString(),
                    ];
                    $data = $client->request('POST', '/api/hris/devices/'.$sync->device_id.'/sync', $payload)->json();
                } else {
                    $data = $client->request('GET', '/api/hris/sync/'.$sync->id)->json();
                }
                if (($data['request_id'] ?? null) !== $sync->id || (string) ($data['device_id'] ?? '') !== $sync->device_id
                    || ($data['command'] ?? null) !== ($employee ? 'DOWNLOAD_EMPLOYEE_DETAILS' : 'DOWNLOAD_DTR_PAYROLL')
                    || (! $employee && (($data['date_from'] ?? null) !== $sync->date_from->toDateString()
                        || ($data['date_to'] ?? null) !== $sync->date_to->toDateString()))) {
                    throw new \DomainException('BiometricsWeb returned a different request or date range.');
                }
                $sync->update(['command_id' => $data['command_id'], 'received_at' => $data['received_at'] ?? null, 'executed_at' => $data['executed_at'] ?? null]);
                if (($data['status'] ?? '') === 'failed' || ($data['sync_state'] ?? '') === 'failed') {
                    $this->fail($sync, $data['message'] ?: 'Device synchronization failed.');
                    $this->acknowledge($sync);

                    return;
                }
                if (($data['status'] ?? '') !== 'done') {
                    $sync->update(['status' => $data['status'] === 'processing' ? 'processing' : 'queued',
                        'message' => $employee ? 'Waiting for device employee export.' : 'Waiting for device Payroll export.',
                        'next_attempt_at' => now()->addSeconds(30)]);

                    return;
                }
                if ($employee && empty($data['export'])) {
                    if (($data['message'] ?? '') !== 'No employees registered.') {
                        throw new \DomainException('Device completed employee sync without an export.');
                    }
                    DB::transaction(function () use ($sync): void {
                        $current = BiometricsSyncRequest::query()->lockForUpdate()->findOrFail($sync->id);
                        if (! $current->terminal()) {
                            $current->update(['status' => 'success', 'result' => ['employee_count' => 0, 'employees' => [], 'export' => null],
                                'message' => 'No employees are registered on this device. HRIS employee records were not changed.',
                                'completed_at' => now(), 'next_attempt_at' => null]);
                        }
                    });
                    $this->acknowledge($sync->fresh());

                    return;
                }
                $sync->update(['status' => 'received', 'message' => $employee ? 'Employee export received; verifying file.' : 'Payroll export received; verifying file.']);
                $binary = $client->request('GET', '/api/hris/sync/'.$sync->id.'/file')->body();
                $sync->update(['status' => 'importing', 'message' => $employee ? 'Validating device employee list.' : 'Validating and importing Payroll DTR.']);
                DB::transaction(function () use ($sync, $binary, $data, $employee): void {
                    $current = BiometricsSyncRequest::query()->lockForUpdate()->findOrFail($sync->id);
                    if ($current->terminal()) {
                        return;
                    }
                    if ($current->deadline_at->isPast() || (! $employee && PayrollPeriod::query()->findOrFail($sync->payroll_period_id)->is_locked)) {
                        throw new \DomainException('Payroll period is locked or synchronization expired.');
                    }
                    $result = $employee
                        ? app(EmployeeSyncReader::class)->read($binary, $data['export'] ?? [])
                        : app(PayrollSyncImporter::class)->import($current, $binary, $data['export'] ?? []);
                    if ($current->deadline_at->isPast() || (! $employee && PayrollPeriod::query()->lockForUpdate()->findOrFail($sync->payroll_period_id)->is_locked)) {
                        throw new \DomainException('Payroll period was locked or synchronization expired before completion.');
                    }
                    $current->update(['status' => ($result['failed'] ?? 0) > 0 ? 'failed' : 'success', 'result' => $result, 'message' => $result['message'],
                        'completed_at' => now(), 'next_attempt_at' => null]);
                });
                $this->acknowledge($sync->fresh());
            } catch (ConnectionException $e) {
                $this->retry($sync, 'Network unavailable. Retrying in the background.');
            } catch (RequestException $e) {
                if (in_array($e->response->status(), [408, 429], true) || $e->response->serverError()) {
                    $this->retry($sync, 'BiometricsWeb is temporarily unavailable. Retrying.');
                } else {
                    $this->fail($sync, 'BiometricsWeb rejected the request (HTTP '.$e->response->status().'). '.($e->response->json('message') ?? ''));
                    $this->acknowledgeFailure($sync);
                }
            } catch (\Throwable $e) {
                report($e);
                $safeMessage = ! ($e instanceof QueryException) && ($e instanceof \DomainException || $e instanceof \RuntimeException);
                $this->fail($sync, $safeMessage ? $e->getMessage() : 'Synchronization failed. Review the sync history.');
                $this->acknowledgeFailure($sync);
            }
        });
    }

    private function acknowledgeFailure(BiometricsSyncRequest $sync): void
    {
        try {
            $this->acknowledge($sync->fresh());
        } catch (\Throwable $e) {
            report($e);
            $this->retry($sync, 'Synchronization failed; waiting to release the remote command.');
        }
    }

    private function retry(BiometricsSyncRequest $sync, string $message): void
    {
        if ($sync->terminal()) {
            $sync->update(['next_attempt_at' => now()->addMinute()]);

            return;
        }
        $sync->increment('attempts');
        $sync->update(['message' => $message, 'next_attempt_at' => now()->addSeconds(min(180, 15 * (2 ** min(4, $sync->attempts))))]);
    }

    private function fail(BiometricsSyncRequest $sync, string $message): void
    {
        if ($sync->fresh()->terminal()) {
            return;
        }
        $sync->update(['status' => 'failed', 'message' => mb_substr($message, 0, 2000), 'completed_at' => now(), 'next_attempt_at' => null]);
    }

    private function acknowledge(BiometricsSyncRequest $sync): void
    {
        if ($sync->remote_acknowledged_at || ! $sync->terminal()) {
            return;
        }
        if ($sync->deadline_at->isPast()) {
            // BiometricsWeb expires the same reservation; stop retrying after the shared deadline.
            $sync->update(['active_device_key' => null, 'next_attempt_at' => now()->addDay()]);

            return;
        }
        try {
            app(BiometricsClient::class)->request('POST', '/api/hris/sync/'.$sync->id.'/finish', ['status' => $sync->status]);
            $sync->update(['remote_acknowledged_at' => now(), 'active_device_key' => null, 'next_attempt_at' => null]);
        } catch (RequestException $e) {
            if (in_array($e->response->status(), [404, 409], true)) {
                $sync->update(['remote_acknowledged_at' => now(), 'active_device_key' => null, 'next_attempt_at' => null]);

                return;
            }
            throw $e;
        }
    }
}
