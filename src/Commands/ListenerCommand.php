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

    public const DISABLE_CACHE_KEY = 'queue-monitor-alerts-disabled';

    private static ?array $alarm_config = [];

    /**
     * Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.
     *
     * @var string
     */
    protected $signature = 'queue-monitor:listener {disable=0} {hours=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command retrieves information about queues sizes and jobs pending + execution time and informing team, if some indexes are over threshold.';

    private array $alarmIdentifications = [];

    private array $jobsAvgPrev;

    /**
     * @var ?Slack
     */
    private ?Slack $slack = null;

    /**
     * @param QueueMonitorQueueRepository $queuesRepository
     * @param QueueMonitorJobsRepository $jobsRepository
     */
    public function __construct(
        private QueueMonitorQueueRepository $queuesRepository,
        private QueueMonitorJobsRepository $jobsRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception|\Psr\SimpleCache\InvalidArgumentException
     *
     */
    public function handle()
    {
        sleep(5);
        self::$alarm_config = config('queue-monitor.alarm');

        $this->alarmIdentifications = Cache::store('redis')->get(self::CACHE_KEY, []);
        $cmd_disabled = Cache::store('redis')->get(self::DISABLE_CACHE_KEY, false);
        if ('disable' === $this->argument('disable')) {
            Cache::store('redis')->put(self::DISABLE_CACHE_KEY, true, Carbon::now()->addHours((int)$this->argument('hours')));
            $cmd_disabled = true;
        }
        if ('enable' === $this->argument('disable')) {
            Cache::store('redis')->forget(self::DISABLE_CACHE_KEY);
            $cmd_disabled = false;
        }

        if (!self::$alarm_config['is_active'] || $cmd_disabled) {
            return 0;
        }

        $messages = [];
        foreach ($this->queuesRepository->getQueuesAlertInfo() as $queue) {
            if (null !== $queue['alert_threshold'] && $queue['size'] >= $queue['alert_threshold'] && $this->validatedAlarm('q-' . $queue['id'])) {
                $messages[] = 'Queue *[' . $queue['connection_name'] . ':' . $queue['queue_name'] . ']* exceed the threshold!' . "\n" .
                    'Size now: *' . $queue['size'] . '*. ' .
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
                $messages[] = $message . "\n\n";
            }
        }

        if (!empty($messages)) {
            $this->sendNotification(implode("\n", $messages));
        }

        Cache::store('redis')->put(self::CACHE_KEY, $this->alarmIdentifications, 4 * 60 + 30); //4m 30s

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

        $this->getSlack()->send('*[GMT ' . now()->format('H:i') . ']*' . "\n" . $message);
    }

    /**
     * @return Slack
     */
    private function getSlack(): Slack
    {
        if (null === $this->slack) {
            $this->slack = app(Slack::class);
        }

        return $this->slack->to(config('queue-monitor.alarm.recipient'));
    }

    /**
     * @param string $alarmId
     *
     * @return bool
     */
    private function validatedAlarm(string $alarmId): bool
    {
        if (now()->subMinutes(4)->subSeconds(10)
            ->gt(
                Carbon::createFromTimestamp($this->alarmIdentifications[$alarmId]))
        ) {
            unlink($this->alarmIdentifications[$alarmId]);
        }

        if (Arr::exists($this->alarmIdentifications, $alarmId)) {
            return false;
        }

        $this->alarmIdentifications[$alarmId] = time();

        return true;
    }

    /**
     * @param array $job
     *
     * @return array
     * @throws \Exception
     *
     */
    private function detectedAlarm(array $job): array
    {
        if (Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.ignore', false)) {
            return [true, ''];
        }

        $failing_count = Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.failing_count', Arr::get(self::$alarm_config, 'jobs_thresholds.failing_count'));
        $pending_count = Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.pending_count', Arr::get(self::$alarm_config, 'jobs_thresholds.pending_count'));
        $pending_time = Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.pending_time', Arr::get(self::$alarm_config, 'jobs_thresholds.pending_time'));
        $pending_time_to_previous = Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.pending_time_to_previous', Arr::get(self::$alarm_config, 'jobs_thresholds.pending_time_to_previous'));
        $execution_time_to_previous = Arr::get(self::$alarm_config, 'jobs_thresholds.exceptions.' . $job['name'] . '.execution_time_to_previous', Arr::get(self::$alarm_config, 'jobs_thresholds.execution_time_to_previous'));

        $messages = [];
        if ((int)$job['FailedCount'] > $failing_count) {
            $messages[] = 'The job ' . $this->getJobLink($job) . ' has been failed *' . $job['FailedCount'] . '* times ' .
                'per last ' . CarbonInterval::seconds(Arr::get(self::$alarm_config, 'jobs_compare_alerts.last'))->cascade()->forHumans();
        }

        if ((int)$job['PendingCount'] > $pending_count) {
            $messages[] = 'The job ' . $this->getJobLink($job) . ' has a queue of *' . $job['PendingCount'] . '*. ';
        }

        if ((int)$job['PendingAvg'] > $pending_time) {
            $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' pending time rise to  *' .
                CarbonInterval::seconds($job['PendingAvg'])->cascade()->forHumans() . '*.';
        }

        if (Arr::exists($this->jobsAvgPrev, $job['id'])) {
            $hour_job_info = $this->jobsAvgPrev[$job['id']];

            if ($hour_job_info['PendingAvg'] > 0
                && $job['PendingAvg'] / $hour_job_info['PendingAvg'] >= $pending_time_to_previous) {
                $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' pending time rise on  *' .
                    round(($job['PendingAvg'] / $hour_job_info['PendingAvg'] - 1) * 100) . '%*.';
            }

            if ($hour_job_info['ExecutingAvg'] > 0
                && $job['ExecutingAvg'] / $hour_job_info['ExecutingAvg'] >= $execution_time_to_previous) {
                $messages[] = 'The job\'s ' . $this->getJobLink($job) . ' execution time rise on  *' .
                    round(($job['ExecutingAvg'] / $hour_job_info['ExecutingAvg'] - 1) * 100) . '%*.';
            }
        }

        return [
            !empty($messages),
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
