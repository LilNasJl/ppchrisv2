<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StreamedDatabaseBackupService extends DatabaseBackupService
{
    public function download(string $format = 'sql'): BinaryFileResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (in_array(strtolower($format), ['gz', 'gzip'], true)) {
            return $this->downloadGz();
        }

        $path = $this->create();

        return response()->download($path, basename($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'application/sql; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    public function downloadGz(): BinaryFileResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (! function_exists('gzopen')) {
            // If zlib is not installed, gracefully fall back to plain SQL
            return $this->download('sql');
        }

        $sqlPath = $this->create();
        $gzPath = $sqlPath.'.gz';

        $fp = @fopen($sqlPath, 'rb');
        if ($fp === false) {
            @unlink($sqlPath);
            throw new \RuntimeException('Unable to read database backup file for compression.');
        }

        $gz = @gzopen($gzPath, 'wb9');
        if ($gz === false) {
            fclose($fp);
            @unlink($sqlPath);
            throw new \RuntimeException('Unable to initialize gzip compression for database backup.');
        }

        while (! feof($fp)) {
            gzwrite($gz, fread($fp, 65536));
        }

        fclose($fp);
        gzclose($gz);
        @unlink($sqlPath);

        return response()->download($gzPath, basename($gzPath), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'application/gzip',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
