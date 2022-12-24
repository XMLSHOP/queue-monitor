<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\SchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\ScheduledTaskFactory;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\ScheduledTasks;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\Task;

class SchedulerMonitorService
{
    public function __construct(
        private SchedulerRepositoryInterface $schedulerRepository,
        private HostRepositoryInterface $hostRepository,
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository,
        private ExceptionRepositoryInterface $exceptionRepository,
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

        (!$scheduler = $this->schedulerRepository->findByName($task->name()))
        && ($scheduler = $this->schedulerRepository->create($task));

        $this->monitorSchedulerRepository->createWithSchedulerAndHost($scheduler, $host);
    }

    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {

        $host = $this->hostRepository->firstOrCreate();
        $task = $this->scheduledTaskFactory->createForEvent($event->task);

        $output = implode('', explode("\n", $event->task->output));
        if(str_contains(strtolower($output), 'exception')) {

        }

        if (!$task->name()) {
            return;
        }

        (!$scheduler = $this->schedulerRepository->findByName($task->name()))
        && ($scheduler = $this->schedulerRepository->create($task));

        $this->monitorSchedulerRepository->updateBySchedulerAndHost($scheduler, $host);
    }

    public function handleTaskFailed(ScheduledTaskFailed $event): void
    {

        $host = $this->hostRepository->firstOrCreate();
        $task = $this->scheduledTaskFactory->createForEvent($event->task);

        if (!$task->name()) {
            return;
        }

        (!$scheduler = $this->schedulerRepository->findByName($task->name()))
        && ($scheduler = $this->schedulerRepository->create($task));

        $exceptionModel = $this->exceptionRepository->createFromThrowable($event->exception);
        $this->monitorSchedulerRepository->updateFailedBySchedulerAndHost($scheduler, $host, $exceptionModel);
    }

    public function syncMonitoredTasks(): void
    {
        $this->scheduledTasks->uniqueTasks()->map(fn(Task $task) => $this->schedulerRepository->updateOrCreate($task));
    }
}
