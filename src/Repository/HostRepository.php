<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;

class HostRepository extends BaseRepository implements HostRepositoryInterface
{
    public function __construct(protected Host $model)
    {
    }

    public function firstOrCreate(): Model|Host
    {
        return $this->model->newQuery()->firstOrCreate([
            'name' => gethostname(),
        ]);
    }

    public function getRunningNowInfo(): Collection
    {
        return $this->model
            ->newQuery()
            ->select(['*'])
            ->with(['monitorScheduler', 'monitorCommand', 'monitorQueue'])
            ->whereHas('monitorScheduler', function ($query) {
                $query->whereNotNull('started_at')->whereNull('finished_at')->where('failed', false);
            })
            ->orWhereHas('monitorQueue', function ($query) {
                $query->whereNotNull('started_at')->whereNull('finished_at')->where('failed', false);
            })
            ->orWhereHas('monitorCommand', function ($query) {
                $query->whereNotNull('started_at')->whereNull('finished_at')->where('failed', false);
            })
            ->get();
    }
}
