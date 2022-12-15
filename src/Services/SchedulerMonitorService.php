<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\SchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\ScheduledTaskFactory;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\ScheduledTasks;
use xmlshop\QueueMonitor\Support\Scheduler\ScheduledTasks\Tasks\Task;

class SchedulerMonitorService
{
    public function __construct(
        private SchedulerRepositoryInterface $schedulerRepository,
        private HostRepositoryInterface $hostRepository,
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository,
        private ScheduledTaskFactory $scheduledTaskFactory,
        private ScheduledTasks $scheduledTasks,
    ) {
    }

    public function handleTaskStarting(ScheduledTaskStarting $event): void
    {
        $host = $this->hostRepository->firstOrCreate();
        $task = $this->scheduledTaskFactory->createForEvent($event->task);

        if (!$task->name()) {
            return;
        }

        if (!$scheduler = $this->schedulerRepository->findByName($task->name())) {
            $this->syncMonitoredTask();
            $scheduler = $this->schedulerRepository->findByName($task->name());
        }

        $this->monitorSchedulerRepository->createWithSchedulerAndHost($scheduler, $host);
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
        $host = $this->hostRepository->firstOrCreate();
        $task = $this->scheduledTaskFactory->createForEvent($event->task);

        if (!$task->name()) {
            return;
        }

        if (!$scheduler = $this->schedulerRepository->findByName($task->name())) {
            $this->syncMonitoredTask();
            $scheduler = $this->schedulerRepository->findByName($task->name());
        }

        $this->monitorSchedulerRepository->updateBySchedulerAndHost($scheduler, $host);
    }

    public function handleTaskFailed(ScheduledTaskFailed $event): void
    {
        $host = $this->hostRepository->firstOrCreate();
        $task = $this->scheduledTaskFactory->createForEvent($event->task);

        if (!$task->name()) {
            return;
        }

        if (!$scheduler = $this->schedulerRepository->findByName($task->name())) {
            $this->syncMonitoredTask();
            $scheduler = $this->schedulerRepository->findByName($task->name());
        }

        $this->monitorSchedulerRepository->updateFailedBySchedulerAndHost($scheduler, $host, $event->exception);
    }

    public function syncMonitoredTask(): void
    {
        $scheduledTasks = $this->scheduledTasks
            ->uniqueTasks()
            ->map(fn (Task $task) => $this->schedulerRepository->updateOrCreate($task));

        $this->schedulerRepository->deleteByIds($scheduledTasks->pluck('id')->toArray());
    }
}
