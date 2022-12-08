<?php

namespace xmlshop\QueueMonitor\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase as BaseTestCase;
use xmlshop\QueueMonitor\Providers\QueueMonitorProvider;
use xmlshop\QueueMonitor\Services\QueueMonitorService;
use xmlshop\QueueMonitor\Tests\Support\BaseJob;

class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        QueueMonitorService::$loadMigrations = true;
        parent::tearDown();
        parent::setUp();

        $this->withoutMockingConsoleOutput();
//        $this->withoutExceptionHandling();
        $this->withExceptionHandling();

        try {
            $this->artisan('queue:table');
            $this->artisan('migrate');
        } catch (InvalidArgumentException $e) {
            // TODO: this command fails locally but is required for travis ci
        }
    }

    public function tearDown(): void
    {
//        parent::tearDown();
        return;
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

    protected function getPackageProviders($app)
    {
        return [
            QueueMonitorProvider::class,
        ];
    }
}
