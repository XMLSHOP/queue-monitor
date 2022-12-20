<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Repository\Interfaces\SchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\Task;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class SchedulerRepository implements SchedulerRepositoryInterface
{
    public function __construct(protected Scheduler $model)
    {
    }

    public function create(Task $task): Scheduler
    {
        return $this->model->newQuery()->create([
            'name' => $task->name(),
            'type' => $task->type(),
            'cron_expression' => $task->cronExpression(),
        ]);
    }

    public function findByName(string $name): ?Scheduler
    {
        return $this->model->newQuery()->where('name', $name)->first();
    }

    public function updateOrCreate(Task $task): Model
    {
        return $this->model->newQuery()->updateOrCreate([
            'name' => $task->name()
        ], [
            'type' => $task->type(),
            'cron_expression' => $task->cronExpression(),
        ]);
    }

    public function deleteByIds(array $ids): void
    {
        $this->model->newQuery()->whereNotIn('id', $ids)->delete();
    }

    public function getList(?string $keyBy = null)
    {
        $query = $this->model->newQuery()->get();
        if (null !== $keyBy) {
            return $query->keyBy($keyBy)->toArray();
        }

        return $query->toArray();
    }
}
