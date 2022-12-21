<?php

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Support\Str;
use Throwable;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;

class CLIFailureHandler
{
    public function handle(string $command_name, Throwable $e)
    {
        /** @var MonitorSchedulerRepositoryInterface $monitorSchedulerRepository */
        $monitorSchedulerRepository = app(MonitorSchedulerRepositoryInterface::class);
        if ($monitorSchedulerRepository->foundByParentProcessId()) {
            $monitorSchedulerRepository->updateFailedExternally($command_name, $e);
        } else {
            /** @var MonitorCommandRepositoryInterface $monitorCommandRepository */
            $monitorCommandRepository = app(MonitorCommandRepositoryInterface::class);
            $monitorCommandRepository->updateFailedExternally($command_name, $e);
        }
    }
}
