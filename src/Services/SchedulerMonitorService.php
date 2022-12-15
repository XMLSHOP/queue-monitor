<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;

class SchedulerMonitorService
{
    public function handleTaskStarting(ScheduledTaskStarting $event): void
    {
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
    }

    public function handleTaskFailed(ScheduledTaskFailed $event): void
    {
    }

    public function handleTaskSkipped(ScheduledTaskSkipped $event): void
    {
    }
}
