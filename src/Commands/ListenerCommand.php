<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Commands;

use Illuminate\Console\Command;
use xmlshop\QueueMonitor\Services\DetectOverLimitListenerService;
use xmlshop\QueueMonitor\Services\DetectOverLimits\CacheChecker;

class ListenerCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.
     *
     * @var string
     */
    protected $signature = 'monitor:listener {action=0} {hours=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.';

    public function __construct(
        private DetectOverLimitListenerService $service,
        private CacheChecker $cacheChecker
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        sleep(1);
        if ($this->getStatus() == 'disabled') {
            return 0;
        }

        $this->service->execute();
        return 0;
    }

    private function getStatus(): string
    {
        $status = 'enabled';

        if ('enable' === $this->argument('action')) {
            $this->cacheChecker->enableAlarm();
            return $status;
        }

        if ('disable' === $this->argument('action')) {
            $this->cacheChecker->disableAlarm((int)$this->argument('hours'));

            $status = 'disabled';
        } else {
            $status = $this->cacheChecker->isEnabledAlarm() ? 'enabled' : 'disabled';
        }

        return $status;
    }
}
