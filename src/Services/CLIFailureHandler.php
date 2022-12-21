<?php

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Support\Str;
use Throwable;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;

class CLIFailureHandler
{
    public function handle(Throwable $e)
    {
        $command_name = null;
        foreach (request()->server('argv') as $arg) {
            if (Str::contains($arg, ':')) {
                $command_name = $arg;
                break;
            }
        }

        /** @var MonitorSchedulerRepositoryInterface $monitorSchedulerRepository */
        $monitorSchedulerRepository = app(MonitorSchedulerRepositoryInterface::class);
        if($monitorSchedulerRepository->foundByParentProcessId()) {
            echo '$monitorSchedulerRepository'."\n";
            $monitorSchedulerRepository->updateFailedExternally($command_name, $e);
        } else {
            echo '$monitorCommandRepository'."\n";
            /** @var MonitorCommandRepositoryInterface $monitorCommandRepository */
            $monitorCommandRepository = app(MonitorCommandRepositoryInterface::class);
            $monitorCommandRepository->updateFailedExternally($command_name, $e);
        }
    }
}
