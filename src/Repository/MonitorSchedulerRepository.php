<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;
use xmlshop\QueueMonitor\Models\Exception;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorScheduler;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\SchedulerRepositoryInterface;

class MonitorSchedulerRepository implements MonitorSchedulerRepositoryInterface
{
    public function __construct(
        private MonitorScheduler $model,
        private SystemResourceInterface $systemResources,
        private SchedulerRepositoryInterface $schedulerRepository,
        private HostRepositoryInterface $hostRepository,
        private ExceptionRepositoryInterface $exceptionRepository
    ) {
    }

    public function createWithSchedulerAndHost(Scheduler $scheduler, Host $host): void
    {
        $this->model
            ->newQuery()
            ->create([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
                'started_at' => now(),
                'ppid' => $this->systemResources->getProcessId(),
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
            'pid' => $this->systemResources->getProcessId(),
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
            'use_memory_mb' => max([$monitorScheduler->use_memory_mb, $this->systemResources->getMemoryUseMb()]),
            'use_cpu' => max([$monitorScheduler->use_cpu, $this->systemResources->getCpuUse()]),
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
            ->orderByDesc('started_at')
            ->first();

        $monitorScheduler && $monitorScheduler->update([
            'exception_id' => $exceptionModel->uuid,
            'finished_at' => now(),
            'failed' => true,
            'pid' => $this->systemResources->getProcessId(),
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
                        $builder->whereNotNull('started_at')->whereNull('finished_at');
                        break;

                    case 'failed':
                        $builder->where('failed', true)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        $builder->where('failed', false)->whereNotNull('finished_at');
                        break;
                }
            })
            ->when(($scheduler_id = $filters['scheduler']) && 'all' !== $scheduler_id, static function (Builder $builder) use ($scheduler_id) {
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

    public function foundByParentProcessId(): bool
    {
        return
            $this->systemResources->isParentProcessScheduler()
            || $this->systemResources->getParentProcessId()
            && $this->model->newQuery()->where('ppid', $this->systemResources->getParentProcessId())->first();
    }

    public function getListRunning(): Collection
    {
        return $this->model
            ->newQuery()
            ->select(['uuid', 'started_at', 'pid'])
            ->whereRelation('host', 'name', $this->systemResources->getHost())
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->get();
    }

    public function updateFailedExternally(string $scheduler_name, Throwable $exception): void
    {
        $scheduler = $this->schedulerRepository->findByName($scheduler_name);
        $host = $this->hostRepository->firstOrCreate();
        $exceptionModel = $this->exceptionRepository->createFromThrowable($exception);

        /** @var MonitorScheduler $monitorScheduler */
        $monitorScheduler = $this->model
            ->newQuery()
            ->where([
                'scheduled_id' => $scheduler->id,
                'host_id' => $host->id,
            ])
            ->orderByDesc('started_at')
            ->first();

        $monitorScheduler && $monitorScheduler->update([
            'exception_id' => $exceptionModel->uuid,
            'finished_at' => now(),
            'failed' => true,
            'pid' => $this->systemResources->getProcessId(),
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorScheduler->started_at, now()),
            'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
            'use_cpu' => $this->systemResources->getCpuUse(),
        ]);
    }

    public function getFailed(): Collection
    {
        return $this->model
            ->newQuery()
            ->where('failed', true)
            ->whereBetween('finished_at', [
                now()->subMinute(),
                now(),
            ])
            ->get();
    }

    public function purge(int $days): void
    {
        $this->model
            ->newQuery()
            ->where('started_at', '<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
