<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Commands;

use Illuminate\Console\Command;
use xmlshop\QueueMonitor\Services\SchedulerMonitorService;

class SyncCommand extends Command
{
    public $signature = 'monitor:sync-scheduler';

    public $description = 'Sync the schedule of the app with the monitor';

    public function handle(SchedulerMonitorService $schedulerMonitorService): int
    {
        try {
            $schedulerMonitorService->syncMonitoredTask();
        } catch (\Throwable $t) {
            $this->error($t->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
