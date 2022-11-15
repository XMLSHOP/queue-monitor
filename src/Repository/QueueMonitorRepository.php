<?php

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\Contracts\QueueMonitorRepositoryContract;

class QueueMonitorRepository extends BaseRepository implements QueueMonitorRepositoryContract
{
    /**
     * @var QueueMonitorQueueRepository
     */
    protected QueueMonitorQueueRepository $queueRepository;

    public function __construct()
    {
        $this->queueRepository = app(QueueMonitorQueueRepository::class);
        parent::__construct();
    }

    public function getModelName(): string
    {
        return QueueMonitorModel::class;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function addQueued(array $data): Model
    {
        $queue = $data['queue'];
        $connection = $data['connection'];
        unset($data['queue'], $data['connection']);
        $data['queue_id'] = $this->queueRepository->firstOrCreate($connection, $queue);
        return $this->create($data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function updateOrCreateStarted(array $data): void
    {
        $job_id = $data['job_id'];
        unset($data['job_id']);

        $queue = $data['queue'];
        $connection = $data['connection'];
        unset($data['queue'], $data['connection']);
        $data['queue_id'] = $this->queueRepository->firstOrCreate($connection, $queue);

        /** @noinspection UnknownColumnInspection */
        $model = $this->model::query()
            ->orderByDesc('queued_at')
            ->firstOrCreate(['job_id' => $job_id,], $data);

        $this->queueRepository->updateWithStarted($model->queue_id, $connection, $queue);

        if ($queuedAt = $model->getQueuedAtExact()) {
            $timeElapsed = (float)$queuedAt->diffInSeconds($data['started_at']) + $queuedAt->diff($data['started_at'])->f;
        }
        $data['time_pending_elapsed'] = $timeElapsed ?? 0.0;
        unset($data['queue'], $data['connection']);
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

    /**
     * @param int $days
     * @return void
     */
    public function purge(int $days): void
    {
        $this->model::query()
            ->where('queued_at','<=', Carbon::now()->subDays($days))
            ->orWhere('started_at','<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
