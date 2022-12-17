<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\Command;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorCommand;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;

class MonitorCommandRepository implements MonitorCommandRepositoryInterface
{
    public function __construct(private MonitorCommand $model, private SystemResourceInterface $systemResources)
    {
    }

    public function createByCommandAndHost(Command $command, Host $host): void
    {
        $this->model
            ->newQuery()
            ->create([
                'command_id' => $command->id,
                'host_id' => $host->id,
                'started_at' => now(),
                'time_elapsed' => 0,
                'failed' => false,
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $this->systemResources->getCpuUse(),
                'created_at' => now(),
            ]);
    }

    public function updateByCommandAndHost(Command $command, Host $host): void
    {
        /** @var MonitorCommand $monitorCommand */
        $monitorCommand = $this->model
            ->newQuery()
            ->where([
                'command_id' => $command->id,
                'host_id' => $host->id,
            ])
            ->whereNull('finished_at')
            ->orderByDesc('started_at')
            ->first();

        if ($monitorCommand) {
            $monitorCommand->update([
                'finished_at' => now(),
                'time_elapsed' => $this->systemResources->getTimeElapsed($monitorCommand->started_at, now()),
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $monitorCommand->use_cpu - $this->systemResources->getCpuUse(),
            ]);
        }
    }

    public function createOrUpdateByCommandAndHost(Command $command, Host $host): void
    {
        // TODO: Implement createOrUpdateByCommandAndHost() method.
    }
}
