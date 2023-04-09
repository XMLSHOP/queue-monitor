<?php

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use xmlshop\QueueMonitor\Models\MonitorScheduler;
use xmlshop\QueueMonitor\Models\Scheduler;
use xmlshop\QueueMonitor\Repository\ExceptionRepository;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;
use xmlshop\QueueMonitor\Repository\MonitorSchedulerRepository;
use xmlshop\QueueMonitor\Services\DetectOverLimits\CacheChecker;
use xmlshop\QueueMonitor\Services\DetectOverLimits\JobsOverLimits;
use xmlshop\QueueMonitor\Services\DetectOverLimits\Notifier;

class DetectOverLimitListenerService
{
    public function __construct(
        private CacheChecker $checker,
        private QueueRepositoryInterface $queuesRepository,
        private MonitorSchedulerRepository $monitorSchedulerRepository,
        private JobsOverLimits $jobsOverLimits,
        private Notifier $notifier
    ) {
    }

    public function execute(): void
    {
        if (!config('monitor.settings.active')) {
            echo "Service disabled";
            return;
        }

        $messages = array_merge(
            $this->detectQueueSizesOverLimits(
                $this->queuesRepository->getQueuesAlertInfo()
            ),
            $this->jobsOverLimits->execute(),
            $this->detectSchedulersFailures(
                $this->monitorSchedulerRepository->getFailed()
            ),
        );
        $this->checker->complete();

        if (!empty($messages)) {
            $this->getNotifier()->send(implode("\n", $messages));
        }

    }

    public function detectQueueSizesOverLimits(array $queues): array
    {
        if (!config('monitor.settings.active-monitor-queue-sizes')) {
            return [];
        }

        $messages = [];
        foreach ($queues as $queue) {
            if ($queue['size'] >= $queue['alert_threshold'] && $this->getChecker()->validate('q-' . $queue['id'])) {
                $messages[] = 'Queue *[' . $queue['connection_name'] . ':'
                    . $queue['queue_name'] . ']* exceed the threshold!'
                    . "\n" .
                    'Size now: *' . $queue['size'] . '*. ' .
                    '<' . route('monitor::queue-sizes') . '|*Queue sizes dashboard*>'
                    . "\n\n";
            }
        }

        return $messages;
    }

    /**
     * @return CacheChecker
     */
    public function getChecker(): CacheChecker
    {
        return $this->checker;
    }

    public function detectSchedulersFailures(Collection $schedulers): array
    {
        if (!config('monitor.settings.active-monitor-scheduler')) {
            return [];
        }

        $messages = [];

        /** @var MonitorScheduler $monitorScheduler */
        foreach ($schedulers as $monitorScheduler) {
            $messages[] = 'Scheduler ' . $this->getSchedulerLink($monitorScheduler->scheduler) . ' failed!' . "\n" .
                '```' . "\n" . $monitorScheduler->exception->exception_message . "\n" . '```' . "\n";
        }

        return $messages;
    }

    /**
     * @return Notifier
     */
    public function getNotifier(): Notifier
    {
        return $this->notifier;
    }

    private function getSchedulerLink(Scheduler $scheduler): string
    {
        return
            '<' . route('monitor::schedulers', [
                'type' => 'failed',
                'scheduler' => $scheduler->id,
                'df' => Carbon::now()
                    ->subSeconds(config('monitor.alarm.jobs_compare_alerts.last'))
                    ->toDateTimeLocalString(),
                'dt' => Carbon::now()->toDateTimeLocalString(),
            ]) . '|*[' . $scheduler->name . ']*>';
    }
}