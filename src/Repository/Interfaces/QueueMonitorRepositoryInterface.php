<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;

interface QueueMonitorRepositoryInterface
{
    public function addQueued(array $data): Model;

    public function updateOrCreateStarted(array $data): void;

    public function findByOrderBy(
        string $field,
        mixed $value,
        array $columns = ['*'],
        string $orderBy = 'id',
        string $orderDirection = 'DESC'
    ): Model;

    public function updateFinished(QueueMonitorModel $model, array $attributes): QueueMonitorModel;

    public function deleteOne(Model $monitor): void;
}
