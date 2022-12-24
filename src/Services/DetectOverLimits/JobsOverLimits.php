<?php

namespace xmlshop\QueueMonitor\Services\DetectOverLimits;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Arr;
use xmlshop\QueueMonitor\Models\Job;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class JobsOverLimits
{
    public function __construct(
        private JobRepositoryInterface $jobsRepository,
        private CacheChecker $checker,
        private SystemResourceInterface $systemResource
    ) {
    }

    public function execute(): array
    {
        if (!config('monitor.settings.active-monitor-queue-jobs')) {
            return [];
        }
        $messages = [];

        $lastFiveMinutes = $this->jobsRepository->getJobsAlertInfo(
            period_seconds: config('monitor.alarm.jobs_compare_alerts.last'),
            offset_seconds: 0,
            with_checkers: true
        );

        $lastHour = $this->jobsRepository->getJobsAlertInfo(
            period_seconds: config('monitor.alarm.jobs_compare_alerts.previous') + config('monitor.alarm.jobs_compare_alerts.last'),
            offset_seconds: config('monitor.alarm.jobs_compare_alerts.last'),
            with_checkers: false
        )->keyBy('id')->toArray();


        /** @var Job $job */
        foreach ($lastFiveMinutes as $job) {
            if ($job->ignore) {
                continue;
            }

            foreach ($job->getAlarmCheckers() as $checker => $checkerValue) {
                [$result, $message] = $this->detectAlarm($job, Arr::get($lastHour, $job->id), $checker);
                if ($result && $this->getChecker()->validate('j-' . $job->id . '-' . $checker)) {
                    $messages[] = $message . "\n";
                }
            }
        }

        return $messages;
    }

    private function detectAlarm(Job $job, ?array $lastHourJob, string $checker): array
    {
        return match ($checker) {
            'failures_amount_threshold' => [
                $condition = $job->failures_amount >= $job->failures_amount_threshold,
                $condition
                    ? 'The job ' . $this->getJobLink($job) . ' has been failed *' . $job->failures_amount . '* times per last ' .
                    CarbonInterval::seconds(config('monitor.alarm.jobs_compare_alerts.last'))->cascade()->forHumans()
                    : null
            ],
            'pending_amount_threshold' => [
                $condition = !$job->ignore_all_besides_failures && $job->pending_amount > $job->pending_amount_threshold,
                $condition ? 'The job ' . $this->getJobLink($job) . ' has a queue of *' . $job->pending_amount . '*. ' : null
            ],
            'pending_time_threshold' => [
                $condition = !$job->ignore_all_besides_failures && $job->pending_time > $job->pending_time_threshold,
                $condition
                    ? 'The job\'s ' . $this->getJobLink($job) . ' pending time rise to  *' .
                    CarbonInterval::seconds($job->pending_time)->cascade()->forHumans() . '*.'
                    : null
            ],
            'pending_time_to_previous_factor' => [
                $condition = !$job->ignore_all_besides_failures && $job->pending_time_to_previous_factor && $lastHourJob
                    && Arr::get($lastHourJob, 'pending_time', 0) > 0
                    && $job->pending_time / $lastHourJob['pending_time'] > $job->pending_time_to_previous_factor
                    && $this->systemResource->getLoadAverage() >= 0.75 * config('monitor.alarm.allowed_loadavg', 10),
                $condition
                    ? 'The job\'s ' . $this->getJobLink($job) . ' pending time rise on  *' .
                    round(($job->pending_time / $lastHourJob['pending_time'] - 1) * 100) . '%*.'
                    : null
            ],
            'execution_time_to_previous_factor' => [
                $condition = !$job->ignore_all_besides_failures && $job->execution_time_to_previous_factor && $lastHourJob
                    && Arr::get($lastHourJob, 'execution_time', 0) > 0
                    && $job->execution_time / $lastHourJob['execution_time'] > $job->execution_time_to_previous_factor,
                $condition
                    ? 'The job\'s ' . $this->getJobLink($job) . ' execution time rise on  *' .
                    round(($job->execution_time / $lastHourJob['execution_time'] - 1) * 100) . '%*.'
                    : null
            ],
            default => [false, null]
        };
    }

    /**
     * @return CacheChecker
     */
    public function getChecker(): CacheChecker
    {
        return $this->checker;
    }


    private function getJobLink(Job $job): string
    {
        return
            '<' . route('monitor::jobs', [
                'type' => 'all',
                'queue' => 'all',
                'job' => $job->id,
                'df' => Carbon::now()
                    ->subSeconds(config('monitor.alarm.jobs_compare_alerts.last'))
                    ->toDateTimeLocalString(),
                'dt' => Carbon::now()->toDateTimeLocalString(),
            ]) . '|*[' . $job->name . ']*>';
    }
}