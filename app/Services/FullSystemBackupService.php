<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class FullSystemBackupService extends DatabaseBackupService
{
    public const APPLICATION_ID = 'philfumes-hris';

    public const FORMAT_VERSION = 1;

    /**
     * @return array<string, string>
     */
    public static function managedDirectories(): array
    {
        return [
            'files/public/profile-photos' => storage_path('app/public/profile-photos'),
            'files/private/memos' => storage_path('app/private/memos'),
            'files/private/leave-attachments' => storage_path('app/private/leave-attachments'),
            'files/private/tickets' => storage_path('app/private/tickets'),
        ];
    }

    public function download(): BinaryFileResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $path = $this->temporaryPath('ppchris-full-backup-');

        try {
            $this->createArchiveAt($path);
        } catch (Throwable $exception) {
            File::delete($path);

            throw $exception;
        }

        $filename = 'ppchris-full-backup-'.now()->format('Ymd-His').'.zip';

        return response()
            ->download($path, $filename, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->deleteFileAfterSend(true);
    }

    public function createSafetyBackup(?string $password = null): string
    {
        $directory = storage_path('app/private/system-backups');
        File::ensureDirectoryExists($directory, 0755, true);
        @chmod($directory, 0755);

        if (! is_writable($directory)) {
            // Secondary fallback
            $directory = storage_path('app/system-backups');
            File::ensureDirectoryExists($directory, 0755, true);
            @chmod($directory, 0755);

            if (! is_writable($directory)) {
                $directory = sys_get_temp_dir();
            }
        }

        $path = $directory.DIRECTORY_SEPARATOR.'safety-before-restore-'.now()->format('Ymd-His').'-'.Str::random(6).'.zip';
        $this->createArchiveAt($path, $password);
        @chmod($path, 0644);
        $this->cleanupSafetyBackups($directory);

        return $path;
    }

    public function createArchiveAt(string $archivePath, ?string $password = null): void
    {
        if ($password !== null && $password !== '' && mb_strlen($password) < 12) {
            throw new RuntimeException('The backup password must contain at least 12 characters.');
        }

        if (! extension_loaded('zip')) {
            throw new RuntimeException('PHP ZIP support is required for a full-system backup.');
        }

        if ($password && ! ZipArchive::isEncryptionMethodSupported(ZipArchive::EM_AES_256, true)) {
            throw new RuntimeException('PHP ZIP support with AES-256 encryption is required for this backup.');
        }

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Full backups are currently supported only for MySQL connections.');
        }

        $sqlPath = $this->temporaryPath('ppchris-database-');
        $sqlStream = null;
        $zip = new ZipArchive;

        try {
            $sqlStream = fopen($sqlPath, 'wb');

            if ($sqlStream === false) {
                throw new RuntimeException('The temporary database backup stream could not be opened.');
            }

            $this->writeBackup($connection->getPdo(), $connection->getDatabaseName(), $sqlStream);
            fclose($sqlStream);
            $sqlStream = null;

            $result = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($result !== true) {
                throw new RuntimeException('The system backup archive could not be created.');
            }

            if ($password) {
                $zip->setPassword($password);
            }

            $manifest = [
                'format_version' => self::FORMAT_VERSION,
                'application' => self::APPLICATION_ID,
                'generated_at' => now()->toIso8601String(),
                'framework_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'encryption' => $password ? 'aes-256' : 'none',
                'database' => [
                    'driver' => $connection->getDriverName(),
                    'name' => $connection->getDatabaseName(),
                    'latest_migration' => Schema::hasTable('migrations')
                        ? DB::table('migrations')->orderByDesc('batch')->orderByDesc('id')->value('migration')
                        : null,
                ],
                'entries' => [],
            ];

            $this->addArchiveFile($zip, $sqlPath, 'database.sql', $password);
            $manifest['entries']['database.sql'] = $this->fileMetadata($sqlPath);

            foreach (self::managedDirectories() as $archiveRoot => $sourceRoot) {
                $this->addManagedDirectory($zip, $sourceRoot, $archiveRoot, $password, $manifest['entries']);
            }

            $manifestJson = json_encode(
                $manifest,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );

            if (! $zip->addFromString('manifest.json', $manifestJson)) {
                throw new RuntimeException('The backup manifest could not be added to the archive.');
            }

            if ($password) {
                $this->encryptEntry($zip, 'manifest.json', $password);
            }

            if (! $zip->close()) {
                throw new RuntimeException('The system backup archive could not be finalized.');
            }

            @chmod($archivePath, 0600);
        } catch (Throwable $exception) {
            if (is_resource($sqlStream)) {
                fclose($sqlStream);
            }

            @$zip->close();
            File::delete($archivePath);

            throw $exception;
        } finally {
            File::delete($sqlPath);
        }
    }

    /**
     * @param  array<string, array{sha256: string, bytes: int}>  $entries
     */
    protected function addManagedDirectory(
        ZipArchive $zip,
        string $sourceRoot,
        string $archiveRoot,
        ?string $password,
        array &$entries,
    ): void {
        $zip->addEmptyDir($archiveRoot);

        if (! is_dir($sourceRoot)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $sourcePath = $file->getPathname();
            $relativePath = ltrim(str_replace('\\', '/', substr($sourcePath, strlen($sourceRoot))), '/');
            $archivePath = $archiveRoot.'/'.$relativePath;

            $this->addArchiveFile($zip, $sourcePath, $archivePath, $password);
            $entries[$archivePath] = $this->fileMetadata($sourcePath);
        }
    }

    protected function addArchiveFile(
        ZipArchive $zip,
        string $sourcePath,
        string $archivePath,
        ?string $password,
    ): void {
        if (! $zip->addFile($sourcePath, $archivePath)) {
            throw new RuntimeException('A required backup file could not be added: '.$archivePath);
        }

        if ($password) {
            $this->encryptEntry($zip, $archivePath, $password);
        }
    }

    protected function encryptEntry(ZipArchive $zip, string $archivePath, string $password): void
    {
        if (! $zip->setEncryptionName($archivePath, ZipArchive::EM_AES_256, $password)) {
            throw new RuntimeException('A backup entry could not be encrypted: '.$archivePath);
        }
    }

    /**
     * @return array{sha256: string, bytes: int}
     */
    protected function fileMetadata(string $path): array
    {
        $checksum = hash_file('sha256', $path);
        $size = filesize($path);

        if ($checksum === false || $size === false) {
            throw new RuntimeException('A backup file could not be inspected.');
        }

        return [
            'sha256' => $checksum,
            'bytes' => $size,
        ];
    }

    protected function temporaryPath(string $prefix): string
    {
        $dir = storage_path('app/private/temp');
        if (! is_dir($dir)) {
            File::ensureDirectoryExists($dir, 0755, true);
            @chmod($dir, 0755);
        }

        $path = is_writable($dir)
            ? @tempnam($dir, $prefix)
            : @tempnam(sys_get_temp_dir(), $prefix);

        if ($path === false) {
            throw new RuntimeException('A secure temporary backup file could not be created.');
        }

        @chmod($path, 0644);

        return $path;
    }

    protected function cleanupSafetyBackups(string $directory): void
    {
        $paths = (array) File::glob($directory.DIRECTORY_SEPARATOR.'safety-before-restore-*.zip');

        usort($paths, fn (string $left, string $right): int => File::lastModified($right) <=> File::lastModified($left));

        foreach ($paths as $index => $path) {
            if ($index >= 3 || File::lastModified($path) < now()->subDays(30)->getTimestamp()) {
                File::delete($path);
            }
        }
    }
}
