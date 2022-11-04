<?php

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Models\Contracts\MonitorContract;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class QueueMonitor
{
    private const TIMESTAMP_EXACT_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @var bool
     */
    public static $loadMigrations = false;

    /**
     * @var \xmlshop\QueueMonitor\Models\Contracts\MonitorContract
     */
    public static $model;

    /**
     * Get the model used to store the monitoring data.
     *
     * @return \xmlshop\QueueMonitor\Models\Contracts\MonitorContract
     */
    public static function getModel(): MonitorContract
    {
        return new self::$model();
    }

    /**
     * Handle Job Queued.
     *
     * @param \Illuminate\Queue\Events\JobQueued $event
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function handleJobQueued(JobQueued $event): void
    {
        self::jobQueued($event->id, $event->connectionName, $event->job);
    }

    /**
     * Handle Job Processing.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     *
     * @return void
     */
    public static function handleJobProcessing(JobProcessing $event): void
    {
        self::jobStarted($event->job);
    }

    /**
     * Handle Job Processed.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     *
     * @return void
     */
    public static function handleJobProcessed(JobProcessed $event): void
    {
        self::jobFinished($event->job);
    }

    /**
     * Handle Job Failing.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function handleJobFailed(JobFailed $event): void
    {
        self::jobFinished($event->job, true, $event->exception);
    }

    /**
     * Handle Job Exception Occurred.
     *
     * @param \Illuminate\Queue\Events\JobExceptionOccurred $event
     *
     * @return void
     * @throws \ReflectionException
     */
    public static function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        self::jobFinished($event->job, true, $event->exception);
    }

    /**
     * Get Job ID.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @return string|int
     */
    public static function getJobId(JobContract $job)
    {
        if ($jobId = $job->getJobId()) {
            return $jobId;
        }

        return sha1($job->getRawBody());
    }

    /**
     * Pending Queue Monitoring for Job.
     *
     * @param int|string $jobId
     * @param string|null $jobConnection
     * @param \Closure|ShouldQueue|string $job
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    protected static function jobQueued(mixed $jobId, ?string $jobConnection, \Closure|ShouldQueue|string $job): void
    {
        $jobClass = get_class($job);
        if ($job instanceof \Illuminate\Events\CallQueuedListener && property_exists($job, 'class')) {
            $jobClass = $job->class;
        }

        if ( ! self::shouldBeMonitored($jobClass)) {
            return;
        }

        $jobClass = is_string($job) ? $job : get_class($job);
        /** @var string $jobQueue */
        $jobQueue = $job?->queue ?? trim(\Illuminate\Support\Facades\Queue::connection($jobConnection)->getQueue(null), '/');
//        $jobsChain = array_map(function ($item) {return get_class(unserialize($item));}, $job?->chained ?? []);

        $now = Carbon::now();

        $model = self::getModel();

        $model::query()->create([
            'job_id' => $jobId,
            'name' => $jobClass,
            'queue' => $jobQueue,
            'queued_at' => $now,
            'queued_at_exact' => $now->format(self::TIMESTAMP_EXACT_FORMAT),
            'attempt' => 0,
        ]);
    }

    /**
     * Start Queue Monitoring for Job.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    protected static function jobStarted(JobContract $job): void
    {
        if ( ! self::shouldBeMonitored($job)) {
            return;
        }

        $now = Carbon::now();

        $model = self::getModel();

        $model::query()
            ->orderByDesc('queued_at')
            ->updateOrCreate([
                'job_id' => self::getJobId($job),
                'attempt' => $job->attempts(),
            ], [
                'name' => $job->resolveName(),
                'queue' => $job->getQueue(),
                'started_at' => $now,
                'started_at_exact' => $now->format(self::TIMESTAMP_EXACT_FORMAT),
            ]);
    }

    /**
     * Finish Queue Monitoring for Job.
     *
     * @param \Illuminate\Contracts\Queue\Job $job
     * @param bool $failed
     * @param \Throwable|null $exception
     *
     * @throws \ReflectionException
     *
     * @return void
     */
    protected static function jobFinished(JobContract $job, bool $failed = false, ?\Throwable $exception = null): void
    {
        if ( ! self::shouldBeMonitored($job)) {
            return;
        }

        $model = self::getModel();

        $monitor = $model::query()
            ->where('job_id', self::getJobId($job))
            ->where('attempt', $job->attempts())
            ->orderByDesc('started_at')
            ->first();

        if (null === $monitor) {
            return;
        }

        /** @var MonitorContract $monitor */
        $now = Carbon::now();

        if ($startedAt = $monitor->getStartedAtExact()) {
            $timeElapsed = (float) $startedAt->diffInSeconds($now) + $startedAt->diff($now)->f;
        }

        $resolvedJob = $job->resolveName();

        if (null === $exception && false === $resolvedJob::keepMonitorOnSuccess()) {
            $monitor->delete();

            return;
        }

        $attributes = [
            'finished_at' => $now,
            'finished_at_exact' => $now->format(self::TIMESTAMP_EXACT_FORMAT),
            'time_elapsed' => $timeElapsed ?? 0.0,
            'failed' => $failed,
        ];

        if (null !== $exception) {
            $attributes += [
                'exception' => mb_strcut((string) $exception, 0, config('queue-monitor.db_max_length_exception', 4294967295)),
                'exception_class' => get_class($exception),
                'exception_message' => mb_strcut($exception->getMessage(), 0, config('queue-monitor.db_max_length_exception_message', 65535)),
            ];
        }

        $monitor->update($attributes);
    }

    /**
     * Determine weather the Job should be monitored, default true.
     *
     * @param JobContract|string $job
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    public static function shouldBeMonitored(JobContract|string $job): bool
    {
        return
            is_string($job) && in_array(IsMonitored::class,
                array_keys((new \ReflectionClass($job))->getTraits())
            )
            || array_key_exists(IsMonitored::class, ClassUses::classUsesRecursive(
                $job->resolveName()
            ));
    }
}
