<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Models\QueueMonitorJobModel;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Tests\Support\IntentionallyFailedException;
use xmlshop\QueueMonitor\Tests\Support\MonitoredFailingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredFailingJobWithHugeExceptionMessage;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithProgressCooldownMockingTime;

class MonitorStateHandlingTest extends TestCase
{
    public function testFailing()
    {
        $this->dispatch(new MonitoredFailingJob());
        $this->workQueue();

        $this->assertCount(1, QueueMonitorModel::all());
        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            QueueMonitorJobModel::query()
                ->where('name_with_namespace', '=', MonitoredFailingJob::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
        $this->assertEquals(IntentionallyFailedException::class, $monitor->exception_class);
        $this->assertEquals('Whoops', $monitor->exception_message);
        $this->assertInstanceOf(IntentionallyFailedException::class, $monitor->getException());
    }

    public function testFailingWithHugeExceptionMessage()
    {
        $this->dispatch(new MonitoredFailingJobWithHugeExceptionMessage());
        $this->workQueue();

        $this->assertCount(1, QueueMonitorModel::all());
        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            QueueMonitorJobModel::query()
                ->where('name_with_namespace', '=', MonitoredFailingJobWithHugeExceptionMessage::class)
                ->first(['id'])->id,
            $monitor->queue_monitor_job_id);
        $this->assertEquals(IntentionallyFailedException::class, $monitor->exception_class);
        $this->assertEquals(str_repeat('x', config('monitor.db.max_length_exception_message')), $monitor->exception_message);
        $this->assertInstanceOf(IntentionallyFailedException::class, $monitor->getException());
    }
}
