<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use xmlshop\QueueMonitor\Repository\Interfaces\CommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;

use function in_array;

class CommandMonitorService
{
    private array $commandsToSkipp = [];

    public function __construct(
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository,
        private CommandRepositoryInterface $commandRepository,
        private HostRepositoryInterface $hostRepository,
        private MonitorCommandRepositoryInterface $monitorCommandRepository,
    ) {
        $this->commandsToSkipp = config('monitor.settings.commandsToSkipMonitor');
    }

    public function handleCommandStarting(CommandStarting $event): void
    {
        if (in_array($event->command, $this->commandsToSkipp, true)) {
            return;
        }

        $host = $this->hostRepository->firstOrCreate();
        $command = $this->commandRepository->firstOrCreateByEvent($event);

        if ($this->monitorSchedulerRepository->foundByParentProcessId()) {
            return;
        }

        $this->monitorCommandRepository->createByCommandAndHost($command, $host);
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        if (in_array($event->command, $this->commandsToSkipp, true)) {
            return;
        }

        $host = $this->hostRepository->firstOrCreate();
        $command = $this->commandRepository->firstOrCreateByEvent($event);

        $this->monitorCommandRepository->updateByCommandAndHost($command, $host);
    }
}
