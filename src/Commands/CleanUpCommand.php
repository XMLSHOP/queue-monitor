<?php

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
    protected $signature = 'queue-monitor:clean-up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command clean data in monitor an queue-sizes tables.';

    /**
     * @param QueueMonitorQueueSizesRepository $queuesSizeRepository
     * @param QueueMonitorRepository $monitorRepository
     */
    public function __construct(
        private QueueMonitorQueueSizesRepository $queuesSizeRepository,
        private QueueMonitorRepository $monitorRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $this->queuesSizeRepository->purge(config('queue-monitor.db.clean_after_days'));
        $this->monitorRepository->purge(config('queue-monitor.db.clean_after_days'));

        return 0;
    }

}