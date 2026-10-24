<?php

namespace App\Services\Biometrics;

use App\Models\BiometricsSyncRequest;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

class BiometricsSyncDtrExport
{
    private const COLUMNS = [
        'uid' => 'Fingerprint ID', 'name' => 'Name',
        'date_in' => 'Date In', 'time_in' => 'Time In',
        'date_out' => 'Date Out', 'time_out' => 'Time Out',
        'sched' => 'Schedule Type', 'sched_start' => 'Schedule Start',
        'sched_end' => 'Schedule End', 'day_part' => 'Day Part',
        'day_count' => 'Device Day Count', 'late' => 'Late',
        'undertime' => 'Undertime', 'overtime' => 'Overtime',
        'early' => 'Early Clock In', 'ot_status' => 'OT Status',
        'branch' => 'Source Station',
    ];

    private const NUMERIC_FIELDS = ['day_count', 'late', 'undertime', 'overtime', 'early'];

    private const COLUMN_WIDTHS = [18, 22, 16, 14, 16, 14, 20, 18, 18, 17, 19, 12, 16, 14, 18, 16, 22];

    public function create(BiometricsSyncRequest $sync): string
    {
        $export = ($sync->result ?? [])['export'] ?? null;
        if (! is_array($export) || empty($export['sha256']) || ! isset($export['record_count'])) {
            throw new RuntimeException('This sync has no complete DTR export metadata.');
        }

        $binary = app(BiometricsClient::class)->request('GET', '/api/hris/sync/'.$sync->id.'/file')->body();
        $page = app(PayrollSyncImporter::class)->archivedRecordsPage(
            $binary, $export, 1, max(1, (int) $export['record_count']), result: $sync->result ?? [],
        );
        $path = tempnam(sys_get_temp_dir(), 'dtr-sync-');
        if ($path === false) {
            throw new RuntimeException('Unable to prepare the Excel file.');
        }

        $writer = new Writer;
        try {
            $writer->openToFile($path);
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('DTR Entries');
            $sheet->setSheetView((new SheetView)->setFreezeRow(2)->setFreezeColumn('C')->setShowGridLines(false));
            foreach (self::COLUMN_WIDTHS as $index => $width) {
                $sheet->setColumnWidth($width, $index + 1);
            }
            $sheet->setAutoFilter(new AutoFilter(0, 1, count(self::COLUMNS) - 1, max(2, count($page['rows']) + 1)));

            $headerStyle = (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('173A63')->setFontSize(11);
            $writer->addRow(Row::fromValues(array_values(self::COLUMNS), $headerStyle)->setHeight(29));

            $baseStyles = [
                (new Style)->setBackgroundColor('FFFFFF')->setFontColor('17212F'),
                (new Style)->setBackgroundColor('F1F5F9')->setFontColor('17212F'),
            ];
            $numericStyles = [
                'late' => (new Style)->setBackgroundColor('FFF2D4')->setFontColor('9A5B00'),
                'undertime' => (new Style)->setBackgroundColor('FDE8E8')->setFontColor('A32D2D'),
                'overtime' => (new Style)->setBackgroundColor('DCF5E8')->setFontColor('126C40'),
                'early' => (new Style)->setBackgroundColor('E8F0FF')->setFontColor('1E55A5'),
            ];

            foreach ($page['rows'] as $index => $entry) {
                $values = [];
                $styles = [];
                foreach (array_keys(self::COLUMNS) as $column => $field) {
                    $value = $entry['record'][$field] ?? null;
                    $values[] = in_array($field, self::NUMERIC_FIELDS, true) && is_numeric($value)
                        ? (float) $value
                        : $this->safeCell($value);
                    $styles[$column] = in_array($field, ['late', 'undertime', 'overtime', 'early'], true) && is_numeric($value) && (float) $value > 0
                        ? $numericStyles[$field]
                        : $baseStyles[$index % 2];
                }
                $writer->addRow(Row::fromValuesWithStyles($values, null, $styles)->setHeight(22));
            }
            $writer->close();
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }

        return $path;
    }

    private function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
