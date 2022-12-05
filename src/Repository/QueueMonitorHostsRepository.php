<?php

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\QueueMonitorHostModel;

class QueueMonitorHostsRepository extends BaseRepository
{
    public function getModelName(): string
    {
        return QueueMonitorHostModel::class;
    }

    /**
     * @return int
     */
    public function firstOrCreate():int
    {
        return $this->model::query()->firstOrCreate(['name' => gethostname()], ['name' => gethostname()])->id;
    }
}
