<?php

namespace Tests\Unit;

use App\Services\Imports\DtrImportService;
use ReflectionMethod;
use Tests\TestCase;

class DtrImportTimeParsingTest extends TestCase
{
    public function test_excel_wrapped_time_preserves_seconds(): void
    {
        $this->assertSame('08:01:57', $this->parseTime('="08:01:57"'));
    }

    public function test_excel_numeric_time_preserves_seconds(): void
    {
        $excelTime = (8 * 3600 + 1 * 60 + 57) / 86400;

        $this->assertSame('08:01:57', $this->parseTime($excelTime));
    }

    protected function parseTime(mixed $value): ?string
    {
        $method = new ReflectionMethod(DtrImportService::class, 'parseTime');
        $method->setAccessible(true);

        return $method->invoke(new DtrImportService, $value);
    }
}
