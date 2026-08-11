<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;
use Throwable;

class SqlDumpRestoreService
{
    public function restore(string $sqlPath): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Database restoration is currently supported only for MySQL connections.');
        }

        $stream = fopen($sqlPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('The database backup inside the archive could not be opened.');
        }

        $pdo = $connection->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $delimiter = ';';
        $statement = '';
        $quote = null;
        $escaped = false;
        $executed = 0;

        try {
            while (($line = fgets($stream)) !== false) {
                if ($quote === null && trim($statement) === '' && preg_match('/^\s*--/', $line) === 1) {
                    continue;
                }

                if (
                    $quote === null
                    && trim($statement) === ''
                    && preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $line, $matches) === 1
                ) {
                    $delimiter = $matches[1];

                    continue;
                }

                $this->consumeLine(
                    $pdo,
                    $line,
                    $delimiter,
                    $statement,
                    $quote,
                    $escaped,
                    $executed,
                );
            }

            if ($quote !== null) {
                throw new RuntimeException('The database backup contains an unterminated quoted value.');
            }

            $remaining = trim($statement);

            if ($remaining !== '') {
                $this->executeStatement($pdo, $remaining);
                $executed++;
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Database restoration stopped because an SQL statement could not be applied.',
                previous: $exception,
            );
        } finally {
            fclose($stream);
        }

        return $executed;
    }

    protected function consumeLine(
        PDO $pdo,
        string $line,
        string $delimiter,
        string &$statement,
        ?string &$quote,
        bool &$escaped,
        int &$executed,
    ): void {
        $length = strlen($line);
        $delimiterLength = strlen($delimiter);

        for ($index = 0; $index < $length; $index++) {
            $character = $line[$index];

            if ($quote !== null) {
                $statement .= $character;

                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($character === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($character === $quote) {
                    if (($line[$index + 1] ?? null) === $quote) {
                        $statement .= $line[++$index];

                        continue;
                    }

                    $quote = null;
                }

                continue;
            }

            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $statement .= $character;

                continue;
            }

            if (
                $delimiterLength > 0
                && substr_compare($line, $delimiter, $index, $delimiterLength) === 0
            ) {
                $sql = trim($statement);
                $statement = '';
                $index += $delimiterLength - 1;

                if ($sql !== '') {
                    $this->executeStatement($pdo, $sql);
                    $executed++;
                }

                continue;
            }

            $statement .= $character;
        }
    }

    protected function executeStatement(PDO $pdo, string $statement): void
    {
        $statement = ltrim($statement, "\xEF\xBB\xBF\x00\x09\x0A\x0D\x20");

        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}
