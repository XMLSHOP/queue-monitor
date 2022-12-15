<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\MonitorQueue;

interface MonitorQueueRepositoryInterface extends BaseRepositoryInterface
{
    public function addQueued(array $data): Model;

    public function updateOrCreateStarted(array $data): void;

    public function updateFinished(MonitorQueue $model, array $attributes): MonitorQueue;

    public function deleteOne(Model $monitor): void;

    public function purge(int $days): void;
}
