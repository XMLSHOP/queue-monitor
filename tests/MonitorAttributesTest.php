<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Models\QueueMonitorJobModel;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithData;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithMergedData;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithMergedDataConflicting;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithProgress;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithProgressCooldown;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJobWithProgressCooldownMockingTime;

class MonitorAttributesTest extends TestCase
{
    public function testData()
    {
        $this->dispatch(new MonitoredJobWithData());
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());

        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithData::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals('{"foo":"bar"}', $monitor->data);
        $this->assertEquals(['foo' => 'bar'], $monitor->getData());
    }

    public function testMergeData()
    {
        $this->dispatch(new MonitoredJobWithMergedData());
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithMergedData::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals('{"foo":"foo","bar":"bar"}', $monitor->data);
        $this->assertEquals(['foo' => 'foo', 'bar' => 'bar'], $monitor->getData());
    }

    public function testMergeDataConflicting()
    {
        $this->dispatch(new MonitoredJobWithMergedDataConflicting());
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithMergedDataConflicting::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals('{"foo":"new"}', $monitor->data);
        $this->assertEquals(['foo' => 'new'], $monitor->getData());
    }

    public function testProgress()
    {
        $this->dispatch(new MonitoredJobWithProgress(50));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgress::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(50, $monitor->progress);
    }

    public function testProgressTooLarge()
    {
        $this->dispatch(new MonitoredJobWithProgress(120));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgress::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(100, $monitor->progress);
    }

    public function testProgressNegative()
    {
        $this->dispatch(new MonitoredJobWithProgress(-20));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgress::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(0, $monitor->progress);
    }

    public function testProgressStandby()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldown(0));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldown::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(0, $monitor->progress);
    }

    public function testProgressStandbyIgnoredValue()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldown(50));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldown::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(50, $monitor->progress);
    }

    public function testProgressStandbyTen()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldown(10));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldown::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(0, $monitor->progress);
    }

    public function testProgressStandbyInFuture()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldownMockingTime(0));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldownMockingTime::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(0, $monitor->progress);
    }

    public function testProgressStandbyInFutureIgnoredValue()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldownMockingTime(50));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldownMockingTime::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(50, $monitor->progress);
    }

    public function testProgressStandbyInFutureTen()
    {
        $this->dispatch(new MonitoredJobWithProgressCooldownMockingTime(10));
        $this->workQueue();

        $this->assertInstanceOf(QueueMonitorModel::class, $monitor = QueueMonitorModel::query()->first());
        /** @noinspection UnknownColumnInspection */
        $this->assertEquals(
            $this->getQueueMonitorModel(MonitoredJobWithProgressCooldownMockingTime::class)->id,
            $monitor->queue_monitor_job_id
        );
        $this->assertEquals(10, $monitor->progress);
    }

    private function getQueueMonitorModel(string $class): ?QueueMonitorJobModel
    {
        return QueueMonitorJobModel::query()
            ->where('name_with_namespace', '=', $class)
            ->first(['id']);
    }
}
