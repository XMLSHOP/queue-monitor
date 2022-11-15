<?php

namespace xmlshop\QueueMonitor\Commands;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Pressutto\LaravelSlack\Slack;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueRepository;

class ListenerCommand extends Command
{
    public const CACHE_KEY = 'queue-monitor-alerts';

    private static ?array $alarm_config = [];

    /**
     * Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.
     *
     * @var string
     */
    protected $signature = 'queue-monitor:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.';

    private array $alarmIdentifications;

    private array $jobsAvgPrev;

    /**
     * @param Slack $slack
     * @param QueueMonitorQueueRepository $queuesRepository
     * @param QueueMonitorJobsRepository $jobsRepository
     */
    public function __construct(
        private Slack $slack,
        private QueueMonitorQueueRepository $queuesRepository,
        private QueueMonitorJobsRepository $jobsRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception|\Psr\SimpleCache\InvalidArgumentException
     *
     * @return int
     */
    public function handle()
    {
        self::$alarm_config = config('queue-monitor.alarm');

        $this->alarmIdentifications = [];
        try {
            $this->alarmIdentifications = Cache::store('redis')->get(self::CACHE_KEY, []);
        } catch (\Exception $e) {
        }

        $messages = [];
        foreach ($this->queuesRepository->getQueuesAlertInfo() as $queue) {
            if (null !== $queue['alert_threshold'] && $queue['size'] >= $queue['alert_threshold'] && $this->validatedAlarm('q-' . $queue['id'])) {
                echo __LINE__ . "\n";
                $messages[] = 'Queue [' . $queue['connection_name'] . ':' . $queue['queue_name'] . '] exceed the threshold' .
                    '(' . $queue['alert_threshold'] . ')!' . "\n" . 'Size: *' . $queue['size'] . '*. ' .
                    '<' . Arr::get(self::$alarm_config, 'routes.queue-sizes') . '|*Queue sizes dashboard*>' . "\n\n";
            }
        }

        $this->jobsAvgPrev = $this->jobsRepository->getJobsAlertInfo(
            period_seconds: Arr::get(self::$alarm_config, 'jobs_compare_alerts.previous') + Arr::get(self::$alarm_config, 'jobs_compare_alerts.last'),
            offset_seconds: Arr::get(self::$alarm_config, 'jobs_compare_alerts.last')
        )->keyBy('id')->toArray();

        $jobLast = $this->jobsRepository->getJobsAlertInfo(
            period_seconds: Arr::get(self::$alarm_config, 'jobs_compare_alerts.last'),
            offset_seconds: 0
        )->toArray();

        foreach ($jobLast as $job) {
            [$result, $message] = $this->detectedAlarm($job);
            if ($result && $this->validatedAlarm('j-' . $job['id'])) {
                echo __LINE__ . "\n";
                $messages[] = $message . "\n\n";
            }
        }

        if ( ! empty($message)) {
            $this->sendNotification(implode("\n", $messages));
        }

        try {
            Cache::store('redis')->put(self::CACHE_KEY, $this->alarmIdentifications, now()->addMinutes(5));
        } catch (\Exception $e) {
        }

        return 0;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function sendNotification(string $message): void
    {
//        match (config('queue-monitor.alarm.channel')) {
//            default => $this->getSlack()->send($message)
//        };

        $this->getSlack()->send('[GMT] ' . now()->format('H:i') . ' ' . $message);
    }

    /**
     * @return Slack
     */
    private function getSlack(): Slack
    {
        return $this->slack->to(config('queue-monitor.alarm.recipient'));
    }

    /**
     * @param string $alarmId
     *
     * @return bool
     */
    private function validatedAlarm(string $alarmId): bool
    {
        if (Arr::exists($this->alarmIdentifications, $alarmId)) {
            return false;
        }
        $this->alarmIdentifications[$alarmId] = time();

        return true;
    }

    /**
     * @param array $job
     *
     * @throws \Exception
     *
     * @return array
     */
    private function detectedAlarm(array $job): array
    {
        $messages = [];

        if ((int) $job['FailedCount'] > Arr::get(self::$alarm_config, 'jobs_thresholds.failing_count')) {
            $messages[] = 'The job ' . $this->getJobLink($job) . ' has been failed *' . $job['FailedCount'] . '* times ' .
                'per last ' . CarbonInterval::seconds(Arr::get(self::$alarm_config, 'jobs_compare_alerts.last'))->cascade()->forHumans();
        }

        if ((int) $job['PendingCount'] > Arr::get(self::$alarm_config, 'jobs_thresholds.pending_count')) {
            $messages[] = 'The job ' . $this->getJobLink($job) . ' has a queue of *' . $job['PendingCount'] . '*. ';
        }

        if ((int) $job['PendingAvg'] > Arr::get(self::$alarm_config, 'jobs_thresholds.pending_time')) {
            $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' pending time rise to  *' .
                CarbonInterval::seconds($job['PendingAvg'])->cascade()->forHumans() . '*.';
        }

        $hour_job_info = $this->jobsAvgPrev[$job['id']];

        if ($job['PendingAvg'] / $hour_job_info['PendingAvg'] >= Arr::get(self::$alarm_config, 'jobs_thresholds.pending_time_to_previous')) {
            $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' pending time rise on  *' .
                round(($job['PendingAvg'] / $hour_job_info['PendingAvg'] - 1) * 100) . '%*.';
        }

        if ($job['PendingAvg'] / $hour_job_info['PendingAvg'] >= Arr::get(self::$alarm_config, 'jobs_thresholds.execution_time_to_previous')) {
            $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' pending time rise on  *' .
                round(($job['PendingAvg'] / $hour_job_info['PendingAvg'] - 1) * 100) . '%*.';
        }

        return [
            ! empty($messages),
            implode("\n", $messages),
        ];
    }

    /**
     * @param array $job
     *
     * @return string
     */
    private function getJobLink(array $job): string
    {
        return
            '<' . Arr::get(self::$alarm_config, 'routes.jobs') .
            '?type=all&queue=all' .
            '&job=' . $job['id'] .
            '&df=' . Carbon::now()
                ->subSeconds(Arr::get(self::$alarm_config, 'jobs_compare_alerts.last'))
                ->toDateTimeLocalString() .
            '&dt=' . Carbon::now()->toDateTimeLocalString()
            . '|*[' . $job['name'] . ']*>';
    }
}
