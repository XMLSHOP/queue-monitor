<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorQueueModel;

class QueueMonitorQueueRepository extends BaseRepository
{

    public function getModelName(): string
    {
        return QueueMonitorQueueModel::class;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array|string $columns
     * @return Collection|static[]
     */
    public function select(array|string $columns = ['*']): Collection|array
    {
        return $this->model::select()->get($columns);
    }

    /**
     * @param string $queue
     * @return Model
     */
    public function addNew(string $queue): Model
    {
        $model = new $this->model();
        $model->queue_name = $queue;
        $model->save();
        return $model;
    }
}
