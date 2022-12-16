<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Services\Scheduler\ScheduledTasks\Tasks\Task;

interface SchedulerRepositoryInterface
{
    public function findByName(string $name): ?Scheduler;

    public function updateOrCreate(Task $task): Model;

    public function deleteByIds(array $ids): void;
}
