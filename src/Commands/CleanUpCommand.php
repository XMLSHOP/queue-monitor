<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Commands;

use Illuminate\Console\Command;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueSizesRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorRepository;

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
        private QueueMonitorQueueSizesRepository $queuesSizeRepository,
        private QueueMonitorRepository $monitorRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->queuesSizeRepository->purge(config('monitor.db.clean_after_days'));
        $this->monitorRepository->purge(config('monitor.db.clean_after_days'));

        return 0;
    }
}
