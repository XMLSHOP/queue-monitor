<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;

class HostRepository extends BaseRepository implements HostRepositoryInterface
{
    public function __construct(protected Host $model)
    {
    }

    public function firstOrCreate():int
    {
        return $this->model->newQuery()->firstOrCreate(['name' => gethostname()], ['name' => gethostname()])->id;
    }
}
