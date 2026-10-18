<?php

namespace Tests\Unit;

use App\Services\Imports\DtrImportService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class DtrImportLockTest extends TestCase
{
    public function test_callback_exception_never_reexecutes_the_import_without_its_lock(): void
    {
        $lock = Mockery::mock();
        Cache::shouldReceive('lock')->once()->with('dtr-import-period:1', 300)->andReturn($lock);
        $lock->shouldReceive('block')->once()->andReturnUsing(fn ($seconds, $callback) => $callback());
        $service = new class extends DtrImportService
        {
            public function check(callable $callback): array
            {
                return $this->withImportLock([['data' => ['payroll_period_id' => 1]]], 'audit-batch', $callback);
            }
        };
        $runs = 0;
        try {
            $service->check(function () use (&$runs): array {
                $runs++;
                throw new \RuntimeException('Test callback failure');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Test callback failure', $e->getMessage());
        }
        $this->assertSame(1, $runs);
    }
}
