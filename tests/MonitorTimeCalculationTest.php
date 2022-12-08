<?php

namespace xmlshop\QueueMonitor\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;

class MonitorTimeCalculationTest extends TestCase
{
    public function testRemaingSeconds()
    {
        $this->assertEquals(
            30,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'), 50)
                ->getRemainingSeconds(Carbon::parse('2020-01-01 10:00:30'))
        );

        $this->assertEquals(
            19,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'), 5)
                ->getRemainingSeconds(Carbon::parse('2020-01-01 10:00:01'))
        );

        $this->assertEquals(
            495,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'), 1)
                ->getRemainingSeconds(Carbon::parse('2020-01-01 10:00:05'))
        );
    }

    public function testElaspedSeconds()
    {
        $this->assertEquals(
            30,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'))
                ->getElapsedSeconds(Carbon::parse('2020-01-01 10:00:30'))
        );

        $this->assertEquals(
            1,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'))
                ->getElapsedSeconds(Carbon::parse('2020-01-01 10:00:01'))
        );

        $this->assertEquals(
            5,
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'))
                ->getElapsedSeconds(Carbon::parse('2020-01-01 10:00:05'))
        );
    }

    public function testElapsedSecondsInterval()
    {
        $this->assertEquals(
            '00:00:05',
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'))
                ->getElapsedInterval(Carbon::parse('2020-01-01 10:00:05'))
                ->format('%H:%I:%S')
        );

        $this->assertEquals(
            '01:00:00',
            $this
                ->createMonitor(Carbon::parse('2020-01-01 10:00:00'))
                ->getElapsedInterval(Carbon::parse('2020-01-01 11:00:00'))
                ->format('%H:%I:%S')
        );
    }

    /**
     * @throws \Exception
     */
    private function createMonitor(Carbon $startedAt, int $progress = null): QueueMonitorModel
    {
        /** @var QueueMonitorModel $monitor */
        $monitor = QueueMonitorModel::query()->create([
            'job_id' => Uuid::generate()->string,
            'queue_monitor_job_id' => rand(0, 10000),
            'queue_id' => 1,
            'host_id' => 1,
            'started_at' => $startedAt,
            'progress' => $progress,
        ]);

        return $monitor;
    }
}
