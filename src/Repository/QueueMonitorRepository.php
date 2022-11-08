<?php

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\Contracts\QueueMonitorRepositoryContract;

class QueueMonitorRepository extends BaseRepository implements QueueMonitorRepositoryContract
{

    public function getModelName(): string
    {
        return QueueMonitorModel::class;
    }

    /**
     * @param array $data
     * @return void
     */
    public function addQueued(array $data): void
    {
        $this->create($data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function updateOrCreateStarted(array $data): void
    {
        $job_id = $data['job_id'];
        unset($data['job_id']);

        /** @noinspection UnknownColumnInspection */
        $model = $this->model::query()
            ->orderByDesc('queued_at')
            ->firstOrCreate(['job_id' => $job_id,], $data);
        $model->update($data);
    }

    /**
     * @param Model $model
     * @param array $attributes
     * @return void
     */
    public function updateFinished(Model $model, array $attributes): void
    {
        $model->update($attributes);
    }

    /**
     * @param Model $monitor
     * @return void
     */
    public function deleteOne(Model $monitor): void
    {
        $monitor->delete();
    }
}