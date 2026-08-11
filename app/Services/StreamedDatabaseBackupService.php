<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamedDatabaseBackupService extends DatabaseBackupService
{
    public function download(): StreamedResponse
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Database backups are currently supported only for MySQL connections.');
        }

        $filename = 'database-backup-'.now()->format('Ymd-His').'-'.Str::random(8).'.sql';

        return response()->streamDownload(function () use ($connection): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                throw new RuntimeException('The database backup download stream could not be opened.');
            }

            try {
                $this->writeBackup($connection->getPdo(), $connection->getDatabaseName(), $stream);
            } finally {
                fclose($stream);
            }
        }, $filename, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'application/sql; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
