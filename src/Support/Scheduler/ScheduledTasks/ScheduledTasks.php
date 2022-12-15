<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\Task;

class ScheduledTasks
{
    private Schedule $schedule;

    private Collection $tasks;

    public function __construct(Schedule $schedule, ScheduledTaskFactory $scheduledTaskFactory)
    {
        $this->schedule = $schedule;

        $this->tasks = collect($this->schedule->events())
            ->filter(fn (Event $event): bool => $event->runsInEnvironment(config('app.env')))
            ->map(fn (Event $event): Task => $scheduledTaskFactory->createForEvent($event));
    }

    public function uniqueTasks(): Collection
    {
        return $this->tasks
            ->filter(fn (Task $task) => $task->shouldMonitor())
            ->reject(fn (Task $task) => empty($task->name()))
            ->unique(fn (Task $task) => $task->name())
            ->values();
    }

    public function duplicateTasks(): Collection
    {
        $uniqueTasksIds = $this->uniqueTasks()
            ->map(fn (Task $task) => $task->uniqueId())
            ->toArray();

        return $this->tasks
            ->filter(fn (Task $task) => $task->shouldMonitor())
            ->reject(fn (Task $task) => empty($task->name()))
            ->reject(fn (Task $task) => in_array($task->uniqueId(), $uniqueTasksIds))
            ->values();
    }

    public function readyForMonitoringTasks(): Collection
    {
        return $this->uniqueTasks()
            ->reject(fn (Task $task) => $task->isBeingMonitored());
    }

    public function monitoredTasks(): Collection
    {
        return $this->uniqueTasks()
            ->filter(fn (Task $task) => $task->isBeingMonitored());
    }

    public function unmonitoredTasks(): Collection
    {
        return $this->tasks->reject(fn (Task $task) => $task->shouldMonitor());
    }

    public function unnamedTasks(): Collection
    {
        return $this->tasks
            ->filter(fn (Task $task) => empty($task->name()))
            ->values();
    }
}
