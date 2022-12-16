<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\Exception;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorScheduler;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;

class MonitorSchedulerRepository implements MonitorSchedulerRepositoryInterface
{
    public function __construct(private MonitorScheduler $model, private SystemResourceInterface $systemResources)
    {
    }

    public function createWithSchedulerAndHost(Scheduler $scheduler, Host $host): void
    {
        $this->model
            ->newQuery()
            ->updateOrInsert([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
            ], [
                'started_at' => now(),
                'time_elapsed' => 0,
                'failed' => false,
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $this->systemResources->getCpuUse(),
                'created_at' => now(),
            ]);
    }

    public function updateBySchedulerAndHost(Scheduler $scheduler, Host $host): void
    {
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
                'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $monitorScheduler->use_cpu - $this->systemResources->getCpuUse(),
            ]);
        }
    }

    public function updateFailedBySchedulerAndHost(Scheduler $scheduler, Host $host, Exception $exceptionModel): void
    {
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
                'exception_id' => $exceptionModel->id,
                'finished_at' => now(),
                'failed' => true,
                'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $monitorScheduler->use_cpu - $this->systemResources->getCpuUse(),
            ]);
        }
    }
}
