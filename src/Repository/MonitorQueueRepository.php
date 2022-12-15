<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorQueueRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;

class MonitorQueueRepository extends BaseRepository implements MonitorQueueRepositoryInterface
{
    public function __construct(
        protected MonitorQueue $model,
        protected QueueRepositoryInterface $queueRepository,
    ) {
    }

    public function addQueued(array $data): Model
    {
        $queueModel =  $this->queueRepository->firstOrCreate($data['connection'], $data['queue']);

        return $this->create([
            'job_id' => $data['job_id'],
            'queue_monitor_job_id' => $data['queue_monitor_job_id'],
            'host_id' => $data['host_id'],
            'queued_at' => $data['queued_at'],
            'attempt' => $data['attempt'],
            'queue_id' => $queueModel->id,
        ]);
    }

    public function updateOrCreateStarted(array $data): void
    {
        $queueModel = $this->queueRepository->firstOrCreate($data['connection'], $data['queue']);

        /** @noinspection UnknownColumnInspection */
        $model = $this->model
            ->newQuery()
            ->orderByDesc('queued_at')
            ->firstOrCreate([
                'job_id' => $data['job_id']
            ], [
                'attempt' => $data['attempt'],
                'queue_monitor_job_id' => $data['queue_monitor_job_id'],
                'host_id' => $data['host_id'],
                'started_at' => $data['started_at'],
                'queue_id' => $queueModel->id,
            ]);

        $this->queueRepository->updateWithStarted($queueModel->id, $data['connection'], $data['queue']);

        if ($queuedAt = $model->getQueued()) {
            $timeElapsed = (float) $queuedAt->diffInSeconds($data['started_at']) + $queuedAt->diff($data['started_at'])->f;
        }

        $model->update([
            'attempt' => $data['attempt'],
            'queue_monitor_job_id' => $data['queue_monitor_job_id'],
            'host_id' => $data['host_id'],
            'started_at' => $data['started_at'],
            'queue_id' => $queueModel->id,
            'time_pending_elapsed' => $timeElapsed ?? 0.0,
        ]);
    }

    public function updateFinished(MonitorQueue $model, array $attributes): MonitorQueue
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
            ->newQuery()
            ->where('queued_at', '<=', Carbon::now()->subDays($days))
            ->orWhere('started_at', '<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
