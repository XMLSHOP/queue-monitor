<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Models\Job;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Tests\Support\IntentionallyFailedException;
use xmlshop\QueueMonitor\Tests\Support\MonitoredFailingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredFailingJobWithHugeExceptionMessage;

class MonitorStateHandlingTest extends TestCase
{
    public function testFailing()
    {
        $this->dispatch(new MonitoredFailingJob());
        $this->workQueue();

        $this->assertCount(1, MonitorQueue::all());
        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());

        $monitorModel = $this->getQueueMonitorModel(MonitoredFailingJob::class);

        /** @noinspection UnknownColumnInspection */
        $this->assertEquals($monitorModel->id, $monitor->queue_monitor_job_id);
        $this->assertEquals(IntentionallyFailedException::class, $monitor->exception->exception_class);
        $this->assertEquals('Whoops', $monitor->exception->exception_message);
        $this->assertInstanceOf(IntentionallyFailedException::class, $monitor->getException());
    }

    public function testFailingWithHugeExceptionMessage()
    {
        $this->dispatch(new MonitoredFailingJobWithHugeExceptionMessage());
        $this->workQueue();

        $this->assertCount(1, MonitorQueue::all());
        $this->assertInstanceOf(MonitorQueue::class, $monitor = MonitorQueue::query()->first());

        $monitorModel = $this->getQueueMonitorModel(MonitoredFailingJobWithHugeExceptionMessage::class);

        $maxLengthExceptionMesssage = str_repeat('x', config('monitor.db.max_length_exception_message'));

        /** @noinspection UnknownColumnInspection */
        $this->assertEquals($monitorModel->id, $monitor->queue_monitor_job_id);
        $this->assertEquals(IntentionallyFailedException::class, $monitor->exception->exception_class);
        $this->assertEquals($maxLengthExceptionMesssage, $monitor->exception->exception_message);
        $this->assertInstanceOf(IntentionallyFailedException::class, $monitor->getException());
    }

    private function getQueueMonitorModel(string $class): ?Job
    {
        return Job::query()
            ->where('name_with_namespace', '=', $class)
            ->first(['id']);
    }
}
