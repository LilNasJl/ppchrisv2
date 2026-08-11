<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class FullSystemRestoreService
{
    private const MAX_UNCOMPRESSED_BYTES = 2147483648;

    public function __construct(
        protected FullSystemBackupService $backupService,
        protected SqlDumpRestoreService $sqlRestoreService,
    ) {}

    /**
     * @return array{generated_at: string|null, statements: int, safety_backup: string}
     */
    public function restore(string $archivePath, string $password): array
    {
        if (! is_file($archivePath)) {
            throw new RuntimeException('The uploaded full-system backup could not be found.');
        }

        @set_time_limit(0);
        ignore_user_abort(true);

        [$stagingPath, $manifest] = $this->stageAndValidateArchive($archivePath, $password);
        $safetyBackup = null;
        $maintenanceEnabled = false;

        try {
            $safetyBackup = $this->backupService->createSafetyBackup($password);

            Artisan::call('down', ['--retry' => 60]);
            $maintenanceEnabled = true;

            $statements = $this->sqlRestoreService->restore($stagingPath.DIRECTORY_SEPARATOR.'database.sql');

            DB::purge();
            DB::reconnect();
            Artisan::call('migrate', ['--force' => true]);

            $this->restoreManagedFiles($stagingPath);

            Artisan::call('storage:link');
            Artisan::call('optimize:clear');

            return [
                'generated_at' => is_string($manifest['generated_at'] ?? null)
                    ? $manifest['generated_at']
                    : null,
                'statements' => $statements,
                'safety_backup' => basename($safetyBackup),
            ];
        } catch (Throwable $exception) {
            $message = 'Full-system restoration failed.';

            if (is_string($safetyBackup)) {
                $message .= ' A safety backup was retained as '.basename($safetyBackup).'.';
            }

            throw new RuntimeException($message, previous: $exception);
        } finally {
            File::deleteDirectory($stagingPath);

            if ($maintenanceEnabled) {
                Artisan::call('up');
            }
        }
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function stageAndValidateArchive(string $archivePath, string $password): array
    {
        $stagingPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ppchris-restore-'.Str::random(20);
        File::ensureDirectoryExists($stagingPath, 0700, true);

        $zip = new ZipArchive;
        $result = $zip->open($archivePath, ZipArchive::RDONLY);

        if ($result !== true) {
            File::deleteDirectory($stagingPath);

            throw new RuntimeException('The uploaded file is not a readable ZIP backup.');
        }

        try {
            $zip->setPassword($password);
            $manifest = $this->readManifest($zip);
            $entries = $this->validateManifest($manifest);
            $this->validateArchiveEntries($zip, $entries);

            foreach ($entries as $entryName => $metadata) {
                $destination = $stagingPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entryName);
                File::ensureDirectoryExists(dirname($destination), 0700, true);
                $this->copyArchiveEntry($zip, $entryName, $destination);

                $size = filesize($destination);
                $checksum = hash_file('sha256', $destination);

                if (
                    $size === false
                    || $checksum === false
                    || $size !== $metadata['bytes']
                    || ! hash_equals($metadata['sha256'], $checksum)
                ) {
                    throw new RuntimeException('Backup integrity validation failed for '.$entryName.'.');
                }
            }

            foreach (array_keys(FullSystemBackupService::managedDirectories()) as $archiveRoot) {
                File::ensureDirectoryExists(
                    $stagingPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $archiveRoot),
                    0700,
                    true,
                );
            }

            $zip->close();

            return [$stagingPath, $manifest];
        } catch (Throwable $exception) {
            $zip->close();
            File::deleteDirectory($stagingPath);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function readManifest(ZipArchive $zip): array
    {
        $index = $zip->locateName('manifest.json', ZipArchive::FL_NOCASE);

        if ($index === false) {
            throw new RuntimeException('The archive does not contain a backup manifest.');
        }

        $stat = $zip->statIndex($index);

        if (! is_array($stat) || (int) ($stat['size'] ?? 0) > 10485760) {
            throw new RuntimeException('The backup manifest is invalid or too large.');
        }

        $content = $zip->getFromIndex($index);

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('The backup password is incorrect or the manifest is damaged.');
        }

        try {
            $manifest = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('The backup manifest is not valid JSON.', previous: $exception);
        }

        if (! is_array($manifest)) {
            throw new RuntimeException('The backup manifest has an invalid structure.');
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array{sha256: string, bytes: int}>
     */
    protected function validateManifest(array $manifest): array
    {
        if (($manifest['application'] ?? null) !== FullSystemBackupService::APPLICATION_ID) {
            throw new RuntimeException('This archive was not created by PhilFumes HRIS.');
        }

        if ((int) ($manifest['format_version'] ?? 0) !== FullSystemBackupService::FORMAT_VERSION) {
            throw new RuntimeException('This backup format is not supported by the installed application version.');
        }

        $latestMigration = data_get($manifest, 'database.latest_migration');

        if (
            is_string($latestMigration)
            && ! File::exists(database_path('migrations/'.$latestMigration.'.php'))
        ) {
            throw new RuntimeException('This backup was created by a newer or incompatible HRIS version.');
        }

        $entries = $manifest['entries'] ?? null;

        if (! is_array($entries) || ! isset($entries['database.sql'])) {
            throw new RuntimeException('The backup manifest does not include the database snapshot.');
        }

        $validated = [];
        $totalBytes = 0;

        foreach ($entries as $entryName => $metadata) {
            if (
                ! is_string($entryName)
                || ! $this->isAllowedFileEntry($entryName)
                || ! is_array($metadata)
                || ! is_string($metadata['sha256'] ?? null)
                || preg_match('/^[a-f0-9]{64}$/i', $metadata['sha256']) !== 1
                || ! is_int($metadata['bytes'] ?? null)
                || $metadata['bytes'] < 0
            ) {
                throw new RuntimeException('The backup manifest contains an unsafe or invalid entry.');
            }

            $totalBytes += $metadata['bytes'];

            if ($totalBytes > self::MAX_UNCOMPRESSED_BYTES) {
                throw new RuntimeException('The backup expands beyond the allowed restoration size.');
            }

            $validated[$entryName] = [
                'sha256' => strtolower($metadata['sha256']),
                'bytes' => $metadata['bytes'],
            ];
        }

        return $validated;
    }

    /**
     * @param  array<string, array{sha256: string, bytes: int}>  $expectedEntries
     */
    protected function validateArchiveEntries(ZipArchive $zip, array $expectedEntries): void
    {
        $seen = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat) || ! is_string($stat['name'] ?? null)) {
                throw new RuntimeException('The ZIP archive contains an unreadable entry.');
            }

            $name = $stat['name'];

            if (! $this->isSafeArchivePath($name)) {
                throw new RuntimeException('The ZIP archive contains an unsafe path.');
            }

            $normalizedName = rtrim($name, '/');

            if (isset($seen[$name])) {
                throw new RuntimeException('The ZIP archive contains duplicate entries.');
            }

            $seen[$name] = true;

            $operatingSystem = 0;
            $attributes = 0;

            if (
                $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)
                && (($attributes >> 16) & 0170000) === 0120000
            ) {
                throw new RuntimeException('Symbolic links are not allowed in a system backup.');
            }

            if (str_ends_with($name, '/')) {
                if (! $this->isManagedArchiveDirectory($normalizedName)) {
                    throw new RuntimeException('The ZIP archive contains an unexpected directory.');
                }

                continue;
            }

            if ($name !== 'manifest.json' && ! isset($expectedEntries[$name])) {
                throw new RuntimeException('The ZIP archive contains an unexpected file: '.$name);
            }
        }

        foreach (array_keys($expectedEntries) as $entryName) {
            if ($zip->locateName($entryName) === false) {
                throw new RuntimeException('A required backup entry is missing: '.$entryName);
            }
        }
    }

    protected function copyArchiveEntry(ZipArchive $zip, string $entryName, string $destination): void
    {
        $source = $zip->getStream($entryName);

        if ($source === false) {
            throw new RuntimeException('The backup password is incorrect or '.$entryName.' is damaged.');
        }

        $target = fopen($destination, 'wb');

        if ($target === false) {
            fclose($source);

            throw new RuntimeException('A temporary restore file could not be created.');
        }

        try {
            if (stream_copy_to_stream($source, $target) === false) {
                throw new RuntimeException('A backup entry could not be extracted.');
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        @chmod($destination, 0600);
    }

    protected function restoreManagedFiles(string $stagingPath): void
    {
        $replacedDirectories = [];

        try {
            foreach (FullSystemBackupService::managedDirectories() as $archiveRoot => $targetPath) {
                $sourcePath = $stagingPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $archiveRoot);
                $previousPath = $targetPath.'.before-restore-'.Str::random(8);

                File::ensureDirectoryExists(dirname($targetPath), 0750, true);

                if (is_dir($targetPath) && ! rename($targetPath, $previousPath)) {
                    throw new RuntimeException('The existing upload directory could not be prepared for restoration.');
                }

                $replacedDirectories[] = [$targetPath, $previousPath];

                File::ensureDirectoryExists($targetPath, 0750, true);

                if (is_dir($sourcePath) && ! File::copyDirectory($sourcePath, $targetPath)) {
                    throw new RuntimeException('Uploaded files could not be restored to '.$archiveRoot.'.');
                }
            }

            foreach ($replacedDirectories as [, $previousPath]) {
                File::deleteDirectory($previousPath);
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($replacedDirectories) as [$targetPath, $previousPath]) {
                File::deleteDirectory($targetPath);

                if (is_dir($previousPath)) {
                    rename($previousPath, $targetPath);
                }
            }

            throw $exception;
        }
    }

    protected function isAllowedFileEntry(string $entryName): bool
    {
        if (! $this->isSafeArchivePath($entryName)) {
            return false;
        }

        if ($entryName === 'database.sql') {
            return true;
        }

        foreach (array_keys(FullSystemBackupService::managedDirectories()) as $archiveRoot) {
            if (str_starts_with($entryName, $archiveRoot.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function isManagedArchiveDirectory(string $entryName): bool
    {
        foreach (array_keys(FullSystemBackupService::managedDirectories()) as $archiveRoot) {
            if ($entryName === $archiveRoot || str_starts_with($entryName, $archiveRoot.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function isSafeArchivePath(string $entryName): bool
    {
        if (
            $entryName === ''
            || strlen($entryName) > 1024
            || str_contains($entryName, "\0")
            || str_contains($entryName, '\\')
            || str_starts_with($entryName, '/')
            || preg_match('/^[a-zA-Z]:/', $entryName) === 1
        ) {
            return false;
        }

        return ! in_array('..', explode('/', $entryName), true);
    }
}
