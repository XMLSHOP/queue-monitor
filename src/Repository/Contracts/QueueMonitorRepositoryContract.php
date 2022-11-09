<?php

namespace xmlshop\QueueMonitor\Repository\Contracts;

use Illuminate\Database\Eloquent\Model;

interface QueueMonitorRepositoryContract
{
    /**
     * @param array $data
     * @return bool
     */
    public function addQueued(array $data): void;

    /**
     * @param string|null $id
     * @param array $data
     * @return bool
     */
    public function updateOrCreateStarted(array $data): void;

    /**
     * @param string $field
     * @param mixed $value
     * @param array $columns
     * @param string $orderBy
     * @param string $orderDirection
     * @return mixed
     */
    public function findByOrderBy(string $field, mixed $value, array $columns = ['*'], string $orderBy = 'id', string $orderDirection = 'DESC');

    /**
     * @param Model $model
     * @param array $attributes
     * @return void
     */
    public function updateFinished(Model $model, array $attributes): void;

    public function deleteOne(Model $monitor);
}