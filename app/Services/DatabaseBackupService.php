<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class DatabaseBackupService
{
    public function create(): string
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Database backups are currently supported only for MySQL connections.');
        }

        $directory = $this->resolveBackupDirectory();
        $this->deleteStaleBackups($directory);

        $filename = 'database-backup-'.now()->format('Ymd-His').'-'.Str::random(8).'.sql';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $stream = @fopen($path, 'wb');

        if ($stream === false) {
            // Secondary fallback to system temporary directory
            $fallbackDir = sys_get_temp_dir();
            $path = $fallbackDir.DIRECTORY_SEPARATOR.$filename;
            $stream = @fopen($path, 'wb');

            if ($stream === false) {
                throw new RuntimeException('The database backup file could not be created. Please verify server storage permissions.');
            }
        }

        @chmod($path, 0644);

        try {
            $this->writeBackup($connection->getPdo(), $connection->getDatabaseName(), $stream);
            fclose($stream);
        } catch (Throwable $exception) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            File::delete($path);

            throw $exception;
        }

        return $path;
    }

    /**
     * @param  resource  $stream
     */
    protected function writeBackup(PDO $pdo, string $database, mixed $stream): void
    {
        $objects = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $tables = [];
        $views = [];

        foreach ($objects as $object) {
            $name = (string) ($object[0] ?? '');
            $type = strtoupper((string) ($object[1] ?? 'BASE TABLE'));

            if ($name === '') {
                continue;
            }

            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $tables[] = $name;
            }
        }

        $this->writeLine($stream, '-- PhilFumes HRIS database backup');
        $this->writeLine($stream, '-- Database: '.$database);
        $this->writeLine($stream, '-- Generated: '.now()->format('Y-m-d H:i:s T'));
        $this->writeLine($stream, '-- Compatible with phpMyAdmin and MySQL CLI');
        $this->writeLine($stream);
        $this->writeLine($stream, 'SET NAMES utf8mb4;');
        $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS = 0;');
        $this->writeLine($stream, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
        $this->writeLine($stream, 'SET AUTOCOMMIT = 0;');
        $this->writeLine($stream);

        $transactionStarted = false;

        try {
            try {
                $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
                $transactionStarted = true;
            } catch (Throwable) {
                // Some shared hosts (Hostinger) restrict consistent snapshot privileges.
                // Fall back to standard transaction or autocommit off.
                try {
                    $pdo->beginTransaction();
                    $transactionStarted = true;
                } catch (Throwable) {
                    $transactionStarted = false;
                }
            }

            foreach ($tables as $table) {
                $this->writeTable($pdo, $stream, $table);
            }

            foreach ($views as $view) {
                $this->writeView($pdo, $stream, $view);
            }

            $this->writeTriggers($pdo, $stream);

            if ($transactionStarted) {
                try {
                    $pdo->exec('COMMIT');
                } catch (Throwable) {
                }
                $transactionStarted = false;
            }
        } finally {
            if ($transactionStarted) {
                try {
                    $pdo->exec('ROLLBACK');
                } catch (Throwable) {
                }
            }
        }

        $this->writeLine($stream, 'COMMIT;');
        $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS = 1;');
        $this->writeLine($stream, 'SET AUTOCOMMIT = 1;');
        $this->writeLine($stream, '-- Backup completed successfully.');
    }

    /**
     * @param  resource  $stream
     */
    protected function writeTable(PDO $pdo, mixed $stream, string $table): void
    {
        $identifier = $this->quoteIdentifier($table);
        $createRow = $pdo->query('SHOW CREATE TABLE '.$identifier)->fetch(PDO::FETCH_ASSOC);
        $createSql = $this->createStatement($createRow);

        $this->writeLine($stream, '-- --------------------------------------------------------');
        $this->writeLine($stream, '-- Table structure for '.$identifier);
        $this->writeLine($stream, 'DROP TABLE IF EXISTS '.$identifier.';');
        $this->writeLine($stream, $createSql.';');
        $this->writeLine($stream);

        $columnRows = $pdo->query('SHOW FULL COLUMNS FROM '.$identifier)->fetchAll(PDO::FETCH_ASSOC);
        $columns = collect($columnRows)
            ->reject(fn (array $column): bool => str_contains(strtoupper((string) ($column['Extra'] ?? '')), 'GENERATED'))
            ->values();

        if ($columns->isEmpty()) {
            return;
        }

        $columnSql = $columns
            ->map(fn (array $column): string => $this->quoteIdentifier((string) $column['Field']))
            ->implode(', ');
        $types = $columns->mapWithKeys(fn (array $column): array => [
            (string) $column['Field'] => (string) ($column['Type'] ?? ''),
        ])->all();

        $statement = $pdo->query('SELECT '.$columnSql.' FROM '.$identifier);
        $batch = [];

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $batch[] = '('.collect($row)
                ->map(fn (mixed $value, string $column): string => $this->quoteValue($pdo, $value, $types[$column] ?? ''))
                ->implode(', ').')';

            if (count($batch) >= 100) {
                $this->writeInsertBatch($stream, $identifier, $columnSql, $batch);
                $batch = [];
            }
        }

        $statement->closeCursor();

        if ($batch !== []) {
            $this->writeInsertBatch($stream, $identifier, $columnSql, $batch);
        }

        $this->writeLine($stream);
    }

    /**
     * @param  resource  $stream
     */
    protected function writeView(PDO $pdo, mixed $stream, string $view): void
    {
        $identifier = $this->quoteIdentifier($view);
        $createRow = $pdo->query('SHOW CREATE VIEW '.$identifier)->fetch(PDO::FETCH_ASSOC);

        $this->writeLine($stream, '-- --------------------------------------------------------');
        $this->writeLine($stream, '-- View structure for '.$identifier);
        $this->writeLine($stream, 'DROP VIEW IF EXISTS '.$identifier.';');
        $this->writeLine($stream, $this->stripDefiner($this->createStatement($createRow)).';');
        $this->writeLine($stream);
    }

    /**
     * @param  resource  $stream
     */
    protected function writeTriggers(PDO $pdo, mixed $stream): void
    {
        try {
            $triggers = $pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            $this->writeLine($stream, '-- Triggers could not be read with the current database privileges.');

            return;
        }

        foreach ($triggers as $trigger) {
            $name = (string) ($trigger['Trigger'] ?? '');

            if ($name === '') {
                continue;
            }

            $identifier = $this->quoteIdentifier($name);
            $createRow = $pdo->query('SHOW CREATE TRIGGER '.$identifier)->fetch(PDO::FETCH_ASSOC);
            $createSql = is_array($createRow)
                ? ($createRow['SQL Original Statement'] ?? $createRow['Create Trigger'] ?? null)
                : null;

            if (! is_string($createSql) || $createSql === '') {
                throw new RuntimeException('A trigger definition could not be read.');
            }

            $this->writeLine($stream, '-- --------------------------------------------------------');
            $this->writeLine($stream, '-- Trigger structure for '.$identifier);
            $this->writeLine($stream, 'DELIMITER ;;');
            $this->writeLine($stream, 'DROP TRIGGER IF EXISTS '.$identifier.';;');
            $this->writeLine($stream, $this->stripDefiner($createSql).';;');
            $this->writeLine($stream, 'DELIMITER ;');
            $this->writeLine($stream);
        }
    }

    /**
     * @param  resource  $stream
     * @param  array<int, string>  $values
     */
    protected function writeInsertBatch(mixed $stream, string $table, string $columns, array $values): void
    {
        $this->writeLine($stream, 'INSERT INTO '.$table.' ('.$columns.') VALUES');
        $this->writeLine($stream, implode(",\n", $values).';');
    }

    protected function quoteValue(PDO $pdo, mixed $value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        $value = (string) $value;

        if (preg_match('/(?:binary|blob|bit)/i', $type) === 1) {
            return '0x'.bin2hex($value);
        }

        $quoted = $pdo->quote($value);

        if ($quoted === false) {
            throw new RuntimeException('A database value could not be safely quoted.');
        }

        return $quoted;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    protected function createStatement(array|false $row): string
    {
        if (! is_array($row)) {
            throw new RuntimeException('A database object definition could not be read.');
        }

        $values = array_values($row);
        $statement = $values[1] ?? null;

        if (! is_string($statement) || $statement === '') {
            throw new RuntimeException('A database object definition is empty.');
        }

        return $statement;
    }

    protected function stripDefiner(string $statement): string
    {
        return preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/i', '', $statement) ?: $statement;
    }

    /**
     * @param  resource  $stream
     */
    protected function writeLine(mixed $stream, string $line = ''): void
    {
        $line .= PHP_EOL;
        $length = strlen($line);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite($stream, substr($line, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('The database backup could not be written completely. Check available storage space and permissions.');
            }

            $offset += $written;
        }
    }

    protected function deleteStaleBackups(string $directory): void
    {
        foreach ((array) File::glob($directory.DIRECTORY_SEPARATOR.'database-backup-*.sql*') as $path) {
            if (File::lastModified($path) < now()->subDay()->getTimestamp()) {
                File::delete($path);
            }
        }
    }

    protected function resolveBackupDirectory(): string
    {
        $primary = storage_path('app/private/database-backups');
        if (! is_dir($primary)) {
            File::ensureDirectoryExists($primary, 0755, true);
            @chmod($primary, 0755);
        }

        if (is_writable($primary)) {
            return $primary;
        }

        $secondary = storage_path('app/database-backups');
        if (! is_dir($secondary)) {
            File::ensureDirectoryExists($secondary, 0755, true);
            @chmod($secondary, 0755);
        }

        if (is_writable($secondary)) {
            return $secondary;
        }

        return sys_get_temp_dir();
    }
}
