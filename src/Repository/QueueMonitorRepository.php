<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueMonitorRepositoryInterface;

class QueueMonitorRepository extends BaseRepository implements QueueMonitorRepositoryInterface
{
    public function __construct(protected QueueMonitorQueueRepository $queueRepository)
    {
        parent::__construct();
    }

    public function getModelName(): string
    {
        return QueueMonitorModel::class;
    }

    public function addQueued(array $data): Model
    {
        $queue = $data['queue'];
        $connection = $data['connection'];
        unset($data['queue'], $data['connection']);
        $data['queue_id'] = $this->queueRepository->firstOrCreate($connection, $queue);

        return $this->create($data);
    }

    public function updateOrCreateStarted(array $data): void
    {
        $job_id = $data['job_id'];
        unset($data['job_id']);

        $queue = $data['queue'];
        $connection = $data['connection'];
        unset($data['queue'], $data['connection']);
        $data['queue_id'] = $this->queueRepository->firstOrCreate($connection, $queue);

        /** @noinspection UnknownColumnInspection */
        $model = $this->model
            ->newModelQuery()
            ->orderByDesc('queued_at')
            ->firstOrCreate(['job_id' => $job_id], $data);

        $this->queueRepository->updateWithStarted($model->queue_id, $connection, $queue);

        if ($queuedAt = $model->getQueued()) {
            $timeElapsed = (float) $queuedAt->diffInSeconds($data['started_at']) + $queuedAt->diff($data['started_at'])->f;
        }

        $data['time_pending_elapsed'] = $timeElapsed ?? 0.0;
        unset($data['queue'], $data['connection']);
        $model->update($data);
    }

    public function updateFinished(QueueMonitorModel $model, array $attributes): QueueMonitorModel
    {
        $model->update($attributes);

        return $model;
    }

    public function deleteOne(Model $monitor): void
    {
        $monitor->delete();
    }

    public function purge(int $days): void
    {
        /** @noinspection UnknownColumnInspection */
        $this->model
            ->newModelQuery()
            ->where('queued_at', '<=', Carbon::now()->subDays($days))
            ->orWhere('started_at', '<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
