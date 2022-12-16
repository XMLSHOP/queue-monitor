<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\ClosureTask;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\CommandTask;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\JobTask;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\ShellTask;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\Task;
use function collect;

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
