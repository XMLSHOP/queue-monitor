<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Queue;
use xmlshop\QueueMonitor\Models\QueueSize;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;

class QueueRepository extends BaseRepository implements QueueRepositoryInterface
{
    public function __construct(protected Queue $model)
    {
    }

    public function addNew(?string $connection, string $queue): Model
    {
        return $this->create([
            'queue_name' => $queue,
            'connection_name' => $connection ?? config('queue.default'),
        ]);
    }

    public function select(array|string $columns = ['*']): Collection
    {
        return $this->getCollection($columns);
    }

    public function firstOrCreate(?string $connection, string $queue): Model
    {
        return $this->model->newQuery()->firstOrCreate([
            'queue_name' => $queue,
            'connection_name' => $connection ?? config('queue.default'),
        ]);
    }

    public function updateWithStarted(int $queue_id, ?string $connection, string $queue): void
    {
        /** @var Queue $model */
        $model = $this->findById($queue_id);

        if (
            ($model->queue_name !== $queue || $model->connection_name !== $connection)
            && (null === $model->queue_name_started && null === $model->connection_name_started)
        ) {
            $model->queue_name_started = $queue;
            $model->connection_name_started = $connection ?? config('queue.default');
            $model->save();
        }
    }

    public function getQueuesAlertInfo(): array
    {
        /** @noinspection UnknownColumnInspection */
        return $this->model->newQuery()
            ->select([
                'mq.id',
                'mq.queue_name',
                'mq.connection_name',
                'mq.alert_threshold',
                'mqs.size',
            ])
            ->from(config('monitor.db.table.queues'), 'mq')
            ->join(config('monitor.db.table.queues_sizes') . ' as mqs', 'mq.id', '=', 'mqs.queue_id')
            ->whereIn('mqs.created_at', function (\Illuminate\Database\Query\Builder $query) {
                $query
                    ->from(with(new QueueSize())->getTable())
                    ->selectRaw('MAX(created_at)');
            })
            ->get()
            ->toArray();
    }
}
