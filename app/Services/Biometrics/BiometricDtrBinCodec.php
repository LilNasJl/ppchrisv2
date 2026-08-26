<?php

namespace App\Services\Biometrics;

use JsonException;
use RuntimeException;

class BiometricDtrBinCodec
{
    private const XOR_KEY = 0xA5;

    private const MAX_RECORD_BYTES = 65535;

    /**
     * @param  iterable<int, array<string, mixed>>  $records
     */
    public function encode(iterable $records): string
    {
        $binary = '';

        foreach ($records as $record) {
            $binary .= $this->encodeRecord($record);
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function encodeRecord(array $record): string
    {
        try {
            $json = json_encode(
                $record,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode biometric D.T.R record.', previous: $exception);
        }

        $length = strlen($json);

        if ($length === 0 || $length > self::MAX_RECORD_BYTES) {
            throw new RuntimeException('A biometric D.T.R record exceeds the BIN format limit.');
        }

        return pack('n', $length).($json ^ str_repeat(chr(self::XOR_KEY), $length));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function decode(string $binary): array
    {
        $records = [];
        $offset = 0;
        $binaryLength = strlen($binary);

        while ($offset + 2 <= $binaryLength) {
            $length = unpack('nlength', substr($binary, $offset, 2))['length'];
            $offset += 2;

            if ($length === 0) {
                break;
            }

            if ($offset + $length > $binaryLength) {
                throw new RuntimeException('The biometric BIN file contains a truncated record.');
            }

            $encoded = substr($binary, $offset, $length);
            $offset += $length;
            $json = $encoded ^ str_repeat(chr(self::XOR_KEY), $length);

            try {
                $record = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('The biometric BIN file contains invalid JSON data.', previous: $exception);
            }

            if (! is_array($record)) {
                throw new RuntimeException('The biometric BIN file contains an invalid record.');
            }

            $records[] = $record;
        }

        return $records;
    }
}
