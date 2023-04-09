<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Commands;

use Illuminate\Console\Command;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorQueueRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueSizeRepositoryInterface;

class CleanUpCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and storing that in table
     *
     * @var string
     */
    protected $signature = 'monitor:clean-up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command clean data in monitor an queue-sizes tables.';

    public function __construct(
        private QueueSizeRepositoryInterface $queuesSizeRepository,
        private MonitorQueueRepositoryInterface $monitorRepository,
        private MonitorCommandRepositoryInterface $monitorCommandRepository,
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->queuesSizeRepository->purge(config('monitor.db.clean_after_days'));
        $this->monitorRepository->purge(config('monitor.db.clean_after_days'));
        $this->monitorCommandRepository->purge(config('monitor.db.clean_after_days'));
        $this->monitorSchedulerRepository->purge(config('monitor.db.clean_after_days'));

        return 0;
    }
}
