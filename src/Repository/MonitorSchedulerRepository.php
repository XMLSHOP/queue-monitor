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
            ->create([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
                'started_at' => now(),
                'ppid' => $this->systemResources->getPid(),
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
            ->whereNull('finished_at')
            ->orderByDesc('started_at')
            ->first();

        $monitorScheduler && $monitorScheduler->update([
            'finished_at' => now(),
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
            'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
            'use_cpu' => $this->systemResources->getCpuUse(),
        ]);
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

        $monitorScheduler && $monitorScheduler->update([
            'exception_id' => $exceptionModel->id,
            'finished_at' => now(),
            'failed' => true,
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
            'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
            'use_cpu' => $this->systemResources->getCpuUse(),
        ]);
    }

    public function getList(Request $request, $filters)
    {
        return $this->model
            ->newQuery()
            ->where(function ($query) use ($filters) {
                $query
                    ->orWhereBetween('started_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween('finished_at', [$filters['df'], $filters['dt']]);
            })
            ->with(['scheduler', 'host'])
            ->when(($type = $filters['type']) && 'all' !== $type, static function (Builder $builder) use ($type) {
                switch ($type) {
                    case 'running':
                        /** @noinspection UnknownColumnInspection */
                        $builder->whereNotNull('started_at')->whereNull('finished_at');
                        break;

                    case 'failed':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', true)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', false)->whereNotNull('finished_at');
                        break;
                }
            })
            ->when(($scheduler_id = $filters['scheduler']) && 'all' !== $scheduler_id, static function (Builder $builder) use ($scheduler_id) {
                /** @noinspection UnknownColumnInspection */
                $builder->where('scheduler_id', $scheduler_id);
            })
            ->orderByDesc('started_at')
            ->orderByDesc('finished_at')
            ->paginate(
                config('monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );
    }

    public function foundByPPid(): bool
    {
        return $this->model->newQuery()
            ->where('ppid', $this->systemResources->getPPid())
            ->exists();
    }
}
