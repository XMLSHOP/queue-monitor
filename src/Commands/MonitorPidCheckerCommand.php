<?php

namespace xmlshop\QueueMonitor\Commands;

use Exception;
use Illuminate\Console\Command;
use xmlshop\QueueMonitor\Models\MonitorCommand;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class MonitorPidCheckerCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and storing that in table
     *
     * @var string
     */
    protected $signature = 'monitor:pid-checker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command clean data in monitor an queue-sizes tables.';

    public function __construct(
        private MonitorCommandRepositoryInterface $monitorCommandRepository,
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository,
        private ExceptionRepositoryInterface $exceptionRepository,
        private SystemResourceInterface $systemResource
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        /** @var MonitorCommand $command */
        foreach ($this->monitorCommandRepository->getListRunning() as $command) {
            if (!$this->systemResource->isProcessIdRunning($command->pid)) {
                $command->finished_at = now();
                $command->time_elapsed = $this->systemResource->getTimeElapsed($command->started_at, now());
                $command->failed = 1;

                $monitorException = $this->exceptionRepository->createFromThrowable(new Exception('No closing event.'));
                $command->exception()->associate($monitorException);
                $command->save();
            }
        }

        foreach ($this->monitorSchedulerRepository->getListRunning() as $scheduler) {
            if (!$this->systemResource->isProcessIdRunning($scheduler->pid)) {
                $scheduler->finished_at = now();
                $scheduler->time_elapsed = $this->systemResource->getTimeElapsed($scheduler->started_at, now());
                $scheduler->failed = 1;

                $monitorException = $this->exceptionRepository->createFromThrowable(new Exception('No closing event.'));
                $scheduler->exception()->associate($monitorException);
                $scheduler->save();
            }
        }

        return 0;
    }
}