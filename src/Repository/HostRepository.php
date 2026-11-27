<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Services\Data\RunningNowDataService;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class HostRepository extends BaseRepository implements HostRepositoryInterface
{
    public function __construct(
        protected Host $model,
        protected RunningNowDataService $runningNowDataService,
        protected SystemResourceInterface $systemResource)
    {
    }

    public function firstOrCreate(): Model|Host
    {
        return $this->model->newQuery()->firstOrCreate([
            'name' => $this->systemResource->getHost(),
        ]);
    }

    public function getRunningNowInfo(): Collection
    {
        return $this->runningNowDataService->beautifierHostSummary(
            $this->model->newQuery()->get()->toArray(),
            [
                'schedulers' => $this->summarizeRunning($this->model->monitorScheduler(), 'scheduler', 'name'),
                'commands' => $this->summarizeRunning($this->model->monitorCommand(), 'command', 'command'),
                'jobs' => $this->summarizeRunning($this->model->monitorQueue(), 'job', 'name'),
            ]
        );
    }

    /**
     * Count running monitors and sum their CPU and memory usage per host and monitored entity name
     * (job, command or scheduler name), without loading the monitor records themselves.
     *
     * @return Collection<int, \stdClass>
     */
    private function summarizeRunning(HasMany $monitors, string $entityRelation, string $entityNameColumn): Collection
    {
        $monitor = $monitors->getRelated();
        /** @var BelongsTo $entity */
        $entity = $monitor->{$entityRelation}();
        $hostId = $monitors->getQualifiedForeignKeyName();
        $entityName = $entity->getRelated()->qualifyColumn($entityNameColumn);

        return $monitor->newQuery()
            ->toBase()
            ->join(
                $entity->getRelated()->getTable(),
                $entity->getQualifiedForeignKeyName(),
                '=',
                $entity->getQualifiedOwnerKeyName()
            )
            ->whereNotNull($monitor->qualifyColumn('started_at'))
            ->whereNull($monitor->qualifyColumn('finished_at'))
            ->where($monitor->qualifyColumn('failed'), false)
            ->select([$hostId . ' as host_id', $entityName . ' as name'])
            ->selectRaw('COUNT(*) as amount, COALESCE(SUM(use_cpu), 0) as cpu, COALESCE(SUM(use_memory_mb), 0) as memory')
            ->groupBy($hostId, $entityName)
            ->orderBy($entityName)
            ->get();
    }
}
