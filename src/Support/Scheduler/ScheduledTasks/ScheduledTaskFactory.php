<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\ClosureTask;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\CommandTask;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\JobTask;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\ShellTask;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\Task;

class ScheduledTaskFactory
{
    private const TASKS = [
        ClosureTask::class,
        JobTask::class,
        CommandTask::class,
        ShellTask::class,
    ];

    public function createForEvent(Event $event): Task
    {
        $taskClass = collect(self::TASKS)
            ->first(fn (string $taskClass) => $taskClass::canHandleEvent($event));

        return new $taskClass($event);
    }
}
