<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Models\Job;
use xmlshop\QueueMonitor\Models\MonitorQueue;
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
        $this->dispatch(new MonitoredJob())->assertDispatched(MonitoredJob::class);

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        $this->assertNotNull($monitor->queued_at);
        $this->assertNull($monitor->started_at);

        $this->workQueue();

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id
        );

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

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredExtendingJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
    }

    public function testDontCreateMonitor()
    {
        $this
            ->dispatch(new UnmonitoredJob())
            ->assertDispatched(UnmonitoredJob::class)
            ->workQueue();

        $this->assertCount(0, MonitorQueue::all());
    }

    public function testDontKeepSuccessfulMonitor()
    {
        $this
            ->dispatch(new MonitoredPartiallyKeptJob())
            ->assertDispatched(MonitoredPartiallyKeptJob::class)
            ->workQueue();

        $this->assertCount(0, MonitorQueue::all());
    }

    public function testDontKeepSuccessfulMonitorFailing()
    {
        $this
            ->dispatch(new MonitoredPartiallyKeptFailingJob())
            ->assertDispatched(MonitoredPartiallyKeptFailingJob::class)
            ->workQueue();

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredPartiallyKeptFailingJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);

    }

    public function testBroadcastingJob()
    {
        $this
            ->dispatch(new MonitoredBroadcastingJob())
            ->assertDispatched(MonitoredBroadcastingJob::class)
            ->workQueue();

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredBroadcastingJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
    }

    public function testDispatchingJobViaDispatchableTrait()
    {
        MonitoredJob::dispatch();

        $this->assertDispatched(MonitoredJob::class);
        $this->workQueue();

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
    }

    public function testDispatchingJobViaDispatchableTraitWithArguments()
    {
        MonitoredJobWithArguments::dispatch('foo');

        $this->assertDispatched(MonitoredJobWithArguments::class);
        $this->workQueue();

        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            Job::query()
                ->where('name_with_namespace', '=', MonitoredJobWithArguments::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
    }
}
