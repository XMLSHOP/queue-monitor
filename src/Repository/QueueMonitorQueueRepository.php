<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorQueueModel;
use xmlshop\QueueMonitor\Models\QueueMonitorQueuesSizesModel;

class QueueMonitorQueueRepository extends BaseRepository
{

    public function getModelName(): string
    {
        return QueueMonitorQueueModel::class;
    }

    /**
     * @param string|null $connection
     * @param string $queue
     * @return Model
     */
    public function addNew(?string $connection, string $queue): Model
    {
        $model = new $this->model();
        $model->queue_name = $queue;
        $model->connection_name = $connection ?? config('queue.default');
        $model->save();
        return $model;
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
     * @param string|null $connection
     * @param string $queue
     * @return int
     */
    public function firstOrCreate(?string $connection, string $queue): int
    {
        return $this->model::query()
            ->firstOrCreate(
                [
                    'queue_name' => $queue,
                    'connection_name' => $connection ?? config('queue.default'),
                ],
                [
                    'queue_name' => $queue,
                    'connection_name' => $connection ?? config('queue.default'),
                ],
            )->id;
    }

    public function updateWithStarted(int $queue_id, ?string $connection, string $queue): void
    {
        $model = $this->findById($queue_id);
        if ($model->queue_name !== $queue || $model->connection_name !== $connection) {
            $model->queue_name_started = $queue;
            $model->connection_name_started = $connection ?? config('queue.default');
            $model->save();
        }
    }

    /**
     * @return array
     */
    public function getQueuesAlertInfo(): array {
        return $this->model::query()
            ->select([
                'mq.id',
                'mq.queue_name',
                'mq.connection_name',
                'mq.alert_threshold',
                'mq.last_alert_sent_at',
                'mqs.size',
            ])
            ->from(config('queue-monitor.db.table.monitor_queues'), 'mq')
            ->join(config('queue-monitor.db.table.monitor_queues_sizes') . ' as mqs', 'mq.id', '=', 'mqs.queue_id')

            ->whereIn('mqs.created_at',  function(\Illuminate\Database\Query\Builder $query) {
                $query
                    ->from(with(new QueueMonitorQueuesSizesModel())->getTable())
                    ->selectRaw('MAX(created_at)');
                }
            )->get()->toArray();
    }

}
