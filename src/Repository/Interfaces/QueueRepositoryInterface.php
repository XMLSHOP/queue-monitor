<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface QueueRepositoryInterface extends BaseRepositoryInterface
{
    public function addNew(?string $connection, string $queue): Model;

    public function select(array|string $columns = ['*']): Collection;

    public function firstOrCreate(?string $connection, string $queue): Model;

    public function getQueuesAlertInfo(): array;
}
