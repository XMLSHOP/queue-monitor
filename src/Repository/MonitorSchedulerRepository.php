<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorScheduler;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;

class MonitorSchedulerRepository implements MonitorSchedulerRepositoryInterface
{
    public function __construct(
        private MonitorScheduler $model,
        private ExceptionRepositoryInterface $exceptionRepository
    ) {
    }

    public function createWithSchedulerAndHost(Scheduler $scheduler, Host $host): void
    {
        $mem = \memory_get_usage(true);
        $cpu = \getrusage();

        $timeUseCpu = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;

        $this->model
            ->newQuery()
            ->create([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
                'started_at' => now(),
                'time_elapsed' => 0,
                'failed' => false,
                'use_memory_mb' => \round($mem/1048576,2),
                'use_cpu' => $timeUseCpu,
                'created_at' => now(),
            ]);
    }

    public function updateBySchedulerAndHost(Scheduler $scheduler, Host $host): void
    {
        $mem = \memory_get_usage(true);
        $cpu = \getrusage();

        $timeUseCpu = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;

        /** @var MonitorScheduler $monitorScheduler */
        $monitorScheduler = $this->model
            ->newQuery()
            ->where([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
            ])
            ->first();

        if ($monitorScheduler) {
            $monitorScheduler->update([
                'finished_at' => now(),
                'time_elapsed' => $monitorScheduler->started_at->diff(now()),
                'use_memory_mb' => \round($mem / 1048576, 2),
                'use_cpu' => $monitorScheduler->use_cpu - $timeUseCpu,
            ]);
        }
    }

    public function updateFailedBySchedulerAndHost(Scheduler $scheduler, Host $host, \Throwable $throwable): void
    {
        $mem = \memory_get_usage(true);
        $cpu = \getrusage();

        $timeUseCpu = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;

        /** @var MonitorScheduler $monitorScheduler */
        $monitorScheduler = $this->model
            ->newQuery()
            ->where([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
            ])
            ->first();

        if ($monitorScheduler) {
            $exceptionModel = $this->exceptionRepository->createFromThrowable($throwable);

            $monitorScheduler->update([
                'exception_id' => $exceptionModel->id,
                'finished_at' => now(),
                'failed' => true,
                'time_elapsed' =>
                    (float) $monitorScheduler->started_at->diffInSeconds(now()) + $monitorScheduler->started_at->diff(now())->f,
                'use_memory_mb' => \round($mem / 1048576, 2),
                'use_cpu' => $monitorScheduler->use_cpu - $timeUseCpu,
            ]);
        }
    }
}
