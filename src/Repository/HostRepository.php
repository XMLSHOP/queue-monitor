<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

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
        $conditionsCallback = static function ($query) {
            $query->whereNotNull('started_at')->whereNull('finished_at')->where('failed', false);
        };

        return $this->runningNowDataService->beautifierHostSummary(
            $this->model
                ->newQuery()
                ->with(['monitorScheduler' => $conditionsCallback])
                ->with(['monitorCommand' => $conditionsCallback])
                ->with(['monitorQueue' => $conditionsCallback])
                ->get()
                ->toArray()
        );
    }
}
