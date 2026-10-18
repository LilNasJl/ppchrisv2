# Biometrics device synchronization

The HR DTR page includes **Sync Biometrics**. Select a device, choose an open payroll period, and submit **DTR Payroll**. The period supplies the complete export range. Employee portal manual imports continue to use their existing workflow.

The same HR device page also offers **Sync Employee**. It requests the device's `DOWNLOAD_EMPLOYEE_DETAILS` export without a payroll period, verifies the export, and stores a read-only employee snapshot in synchronization history. Open **View details** to search the device ID, UID, name, account type, schedule, fingerprint IDs/count, and device registration date. This does not create or update HRIS employees, fingerprint mappings, DTR, or payroll. The device stores a registration date, not an exact creation timestamp.

**Sync Device** and **Sync Employee** share one device reservation across all HR users. Both buttons stay disabled until the previous request finishes and BiometricsWeb acknowledges the terminal state. The existing recovery worker releases stale reservations after the deadline. A device with no employees returns a successful empty snapshot only when running the updated firmware that reports `No employees registered.`; older firmware reports a failed employee command instead. Deploy the BiometricsWeb API change before exposing the new HRIS action.

## Deployment

Deploy both repositories before enabling sync. Apply additive migrations in each application:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Existing DTR, command, employee, and payroll data are retained. No data reset is required.

Set the same randomly generated secret (at least 32 characters) in both environments:

```dotenv
BIOMETRICS_SYNC_SECRET=replace-with-a-long-random-shared-secret
```

In PPCHRISV2, configure the BiometricsWeb root URL (without /api):

```dotenv
BIOMETRICS_SYNC_URL=https://biometrics.philfumes.net
```

In BiometricsWeb, configure the reachable PPCHRISV2 callback:

```dotenv
BIOMETRICS_SYNC_CALLBACK_URL=https://your-hris-domain/api/biometrics-sync/callback
```

A hosted server cannot call a browser computer's 127.0.0.1 address. During local development callbacks may be omitted: the HR scheduled recovery process polls authenticated command status and finishes imports autonomously. Both applications' workers and schedulers must run regardless of callback availability.

If a new request remains **Pending** with no command ID, it has been saved in PPCHRISV2 but its `biometrics` queue has no consumer. A queue worker running from another Laravel directory (for example `/var/www/html/ppchris`) cannot process PPCHRISV2 jobs. The device is contacted only after the PPCHRISV2 worker starts. The recovery task must also run each minute to collect the export after the device completes it.

All production endpoints require HTTPS. Do not expose the shared secret or device keys to JavaScript.

## Background processing

Run this worker in each application under a process manager:

```bash
php artisan queue:work biometrics --queue=biometrics --sleep=3 --tries=3 --timeout=240
```

Run Laravel's scheduler every minute in each application using the application's absolute directory and installed PHP path:

```cron
* * * * * cd /absolute/application/path && php artisan schedule:run >> /dev/null 2>&1
```

On hosting without persistent processes, run a dedicated cron job every minute instead of the persistent worker:

```bash
php artisan queue:work biometrics --queue=biometrics --stop-when-empty --max-time=50 --timeout=240 --tries=3
```

For the local Ubuntu installation, reusable systemd worker and recovery units are in `deploy/systemd/`. A user-owned cron alternative is in `deploy/cron/joelyn-with-biometrics-sync.cron`; it runs both tasks each minute with a lock around the worker. That example includes two pre-existing jobs from this particular machine. Inspect the active crontab before installing it elsewhere.

Use the hosting scheduler's overlap prevention. The dedicated database connection has a 330-second retry-after, longer than the job timeout. A single import is committed transactionally. Missed jobs, callbacks, and temporary network failures are recovered by `biometrics:recover`, scheduled every minute.

## Repeat imports and results

Statuses: pending, queued, processing, received, importing, success, failed. Only success and failed are terminal. One device reservation stays active through device export and HR import.

- New completed sessions are imported.
- Identical sessions are skipped.
- Active sessions are excluded by the firmware Payroll export. If a provisional row appears, the integration defers it; a later completed session is imported.
- Existing conflicting, manually corrected, approved, or finalized missing-punch entries are preserved and reported for HR review. Sync does not overwrite them.
- Regular absence candidates use the existing HRIS eligibility checks.
- Validation errors cancel the complete import; the history contains row errors.
- Locked payroll periods reject imports, including a period locked after a request was submitted.
- The original BIN stays in BiometricsWeb for audit and authenticated manual download.
- The firmware reports a no-records export as Failed / No records found. No attendance is inferred from an empty result.

Limits: 10 MiB and 10,000 records per export; payroll range at most 32 inclusive days. Pending commands expire in 3 minutes, device processing in 5 minutes, the synchronization in 15 minutes. Network retries retain the same request UUID. Late results cannot reopen terminal requests. A retry after failure is a new history request.

## Pairing security

The existing /api/devices/credentials endpoint now requires an X-Pairing-Token header matching BIOMETRICS_PAIRING_TOKEN (at least 32 characters). Existing enrolled devices retain their credentials. New automatic credential discovery must supply that header; existing device codes/API keys can still be entered manually. Do not enable public credential dumps.

## Integration endpoints

BiometricsWeb exposes signed endpoints under /api/hris:
GET /devices; POST /devices/{device}/sync; GET /sync/{requestId}; GET /sync/{requestId}/file; POST /sync/{requestId}/finish.

PPCHRISV2 accepts POST /api/biometrics-sync/callback.

Sign the method, request URI (including any query), timestamp, and SHA-256 body hash with HMAC-SHA256. Signatures expire after five minutes. Request UUIDs provide idempotency, and the integration only queues DOWNLOAD_DTR_PAYROLL.

## Verification

Automated tests use in-memory SQLite and simulated HTTP/device responses. Physical device timing, real simultaneous MySQL sessions, hosting cron availability, and production connectivity require a one-device deployment pilot.

