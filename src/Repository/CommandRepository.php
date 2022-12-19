<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Command;
use xmlshop\QueueMonitor\Repository\Interfaces\CommandRepositoryInterface;

class CommandRepository implements CommandRepositoryInterface
{
    public function __construct(protected Command $model)
    {
    }

    public function firstOrCreateByEvent(CommandFinished|CommandStarting $event): Command|Model
    {
        return $this->model->newQuery()->firstOrCreate([
            'command' => $event->command
        ]);
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
