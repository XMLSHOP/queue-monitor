<?php

namespace xmlshop\QueueMonitor\Commands;

use Illuminate\Console\Command;
use Pressutto\LaravelSlack\Slack;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueSizesRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorRepository;

class ListenerCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.
     *
     * @var string
     */
    protected $signature = 'queue-monitor:listener';

    /**
     * @var int
     */
    protected $between_alerts = 5 * 60;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.';

    /**
     * @param QueueMonitorQueueSizesRepository $queuesSizeRepository
     * @param QueueMonitorRepository $monitorRepository
     */
    public function __construct(
        private Slack $slack,
        private QueueMonitorQueueRepository $queuesRepository,
        private QueueMonitorRepository $monitorRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     *
     */
    public function handle()
    {
        $messages = [];

        foreach ($this->queuesRepository->getQueuesAlertInfo() as $queue) {
            if (null !== $queue['alert_threshold']
                && $queue['alert_threshold'] <= $queue['size']) {
                $messages[] = 'Queue [' . $queue['connection_name'] . ':' . $queue['queue_name'] . '] exceed the threshold' .
                    '(' . $queue['alert_threshold'] . ')!' . "\n" . 'Size: **' . $queue['size'] . '**. ';
            }
        }

        #TODO: Job failed 2 times per last 5 minutes

        #TODO: AVG Job pending time rised to 1m

        #TODO: AVG Job execution time rised +50%

        if (!empty($messages)) {
            $this->getSlack()->to('#evri-hermes-notifications')->send($messages);
        }
        return 0;
    }

    /**
     * @return Slack
     */
    public function getSlack(): Slack
    {
        return $this->slack;
    }
}
