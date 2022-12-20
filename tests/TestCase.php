<?php

namespace xmlshop\QueueMonitor\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as BaseTestCase;
use xmlshop\QueueMonitor\Providers\MonitorProvider;
use xmlshop\QueueMonitor\Services\QueueMonitorService;
use xmlshop\QueueMonitor\Tests\Support\BaseJob;

class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        QueueMonitorService::$loadMigrations = true;

        parent::setUp();

        $this->withoutMockingConsoleOutput();
        $this->withoutExceptionHandling();

        try {
            $this->artisan('queue:table');
        } catch (\Throwable) {}

        $this->artisan('migrate');
    }

    protected function dispatch(BaseJob $job): self
    {
        dispatch($job);

        return $this;
    }

    protected function assertDispatched(string $jobClass): self
    {
        $rows = DB::select('SELECT * FROM jobs');

        $this->assertCount(1, $rows);
        $this->assertEquals($jobClass, json_decode($rows[0]->payload)->displayName);

        return $this;
    }

    protected function workQueue(): void
    {
        $this->artisan('queue:work --once --sleep 1');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MonitorProvider::class,
        ];
    }
}
