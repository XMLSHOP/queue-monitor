<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use xmlshop\QueueMonitor\Models\Command;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorCommand;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;

class MonitorCommandRepository implements MonitorCommandRepositoryInterface
{
    public function __construct(protected MonitorCommand $model)
    {
    }

    public function createOrUpdateByCommandAndHost(Command $command, Host $host): void
    {
        $mem = \memory_get_usage(true);
        $cpu = \getrusage();

        $timeUseCpu = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;

        $this->model
            ->newQuery()
            ->firstOrCreate([
                'command_id' => $command->id,
                'host_id' => $host->id,
            ], [
                'started_at' => now(),
                'time_elapsed' => 0,
                'failed' => false,
                'use_memory_mb' => \round($mem/1048576,2),
                'use_cpu' => $timeUseCpu,
                'created_at' => now(),
            ]);
    }

    public function updateByCommandAndHost(Command $command, Host $host): void
    {
        $mem = \memory_get_usage(true);
        $cpu = \getrusage();

        $timeUseCpu = $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;

        /** @var MonitorCommand $monitorCommand */
        $monitorCommand = $this->model
            ->newQuery()
            ->where([
                'command_id' => $command->id,
                'host_id' => $host->id,
            ])
            ->first();

        if ($monitorCommand) {
            $monitorCommand->update([
                'finished_at' => now(),
                'time_elapsed' =>
                    (float) $monitorCommand->started_at->diffInSeconds(now()) + $monitorCommand->started_at->diff(now())->f,
                'use_memory_mb' => \round($mem / 1048576, 2),
                'use_cpu' => $monitorCommand->use_cpu - $timeUseCpu,
            ]);
        }
    }
}
