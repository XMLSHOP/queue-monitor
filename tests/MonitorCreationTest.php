<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Tests\Support\MonitoredBroadcastingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredExtendingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithArguments;
use xmlshop\QueueMonitor\Tests\Support\MonitoredPartiallyKeptFailingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredPartiallyKeptJob;
use xmlshop\QueueMonitor\Tests\Support\UnmonitoredJob;

class MonitorCreationTest extends TestCase
{
    public function testCreateMonitor()
    {
        $this
            ->dispatch(new MonitoredJob())
            ->assertDispatched(MonitoredJob::class);

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertNotNull($monitor->queued_at);
        $this->assertNull($monitor->started_at);
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredJob::class, $monitor->name);

        $this->assertNotNull($monitor->queued_at);
        $this->assertNotNull($monitor->started_at);
        $this->assertNotNull($monitor->finished_at);
    }

    public function testCreateMonitorFromExtending()
    {
        $this
            ->dispatch(new MonitoredExtendingJob())
            ->assertDispatched(MonitoredExtendingJob::class)
            ->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredExtendingJob::class, $monitor->name);
    }

    public function testDontCreateMonitor()
    {
        $this
            ->dispatch(new UnmonitoredJob())
            ->assertDispatched(UnmonitoredJob::class)
            ->workQueue();

        $this->assertCount(0, QueueMonitorModel::all());
    }

    public function testDontKeepSuccessfulMonitor()
    {
        $this
            ->dispatch(new MonitoredPartiallyKeptJob())
            ->assertDispatched(MonitoredPartiallyKeptJob::class)
            ->workQueue();

        $this->assertCount(0, QueueMonitorModel::all());
    }

    public function testDontKeepSuccessfulMonitorFailing()
    {
        $this
            ->dispatch(new MonitoredPartiallyKeptFailingJob())
            ->assertDispatched(MonitoredPartiallyKeptFailingJob::class)
            ->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredPartiallyKeptFailingJob::class, $monitor->name);
    }

    public function testBroadcastingJob()
    {
        $this
            ->dispatch(new MonitoredBroadcastingJob())
            ->assertDispatched(MonitoredBroadcastingJob::class)
            ->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredBroadcastingJob::class, $monitor->name);
    }

    public function testDispatchingJobViaDispatchableTrait()
    {
        MonitoredJob::dispatch();

        $this->assertDispatched(MonitoredJob::class);
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredJob::class, $monitor->name);
    }

    public function testDispatchingJobViaDispatchableTraitWithArguments()
    {
        MonitoredJobWithArguments::dispatch('foo');

        $this->assertDispatched(MonitoredJobWithArguments::class);
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        $this->assertEquals(MonitoredJobWithArguments::class, $monitor->name);
    }
}
