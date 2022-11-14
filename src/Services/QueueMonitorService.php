<?php

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\Contracts\QueueMonitorRepositoryContract;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorRepository;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class QueueMonitorService
{
    private const TIMESTAMP_EXACT_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @var bool
     */
    public static $loadMigrations = false;

    /**
     * @var string
     */
    public static $model = \xmlshop\QueueMonitor\Models\QueueMonitorModel::class;

    /**
     * Get the model used to store the monitoring data.
     *
     * @return \xmlshop\QueueMonitor\Models\QueueMonitorModel
     */
    public static function getModel(): QueueMonitorModel
    {
        return new self::$model();
    }

    private static $repository = QueueMonitorRepository::class;

    /**
     * Get the model used to store the monitoring data.
     *
     * @return QueueMonitorRepositoryContract
     */
    public static function getRepository(): QueueMonitorRepositoryContract
    {
        return new self::$repository();
    }

    /**
     * Handle Job Queued.
     *
     * @param \Illuminate\Queue\Events\JobQueued $event
     *
     * @return void
     * @throws \ReflectionException
     *
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
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
     *
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
     *
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
    public static function getJobId(Job $job)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        if ($jobId = $job->getJobId()) {
            return $jobId;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return sha1($job->getRawBody());
    }

    /**
     * Pending Queue Monitoring for Job.
     *
     * @param int|string $jobId
     * @param string|null $jobConnection
     * @param \Closure|ShouldQueue|string $job
     *
     * @return void
     * @throws \ReflectionException
     *
     */
    protected static function jobQueued(mixed $jobId, ?string $jobConnection, \Closure|ShouldQueue|string $job): void
    {
        $jobClass = get_class($job);

        if (!self::shouldBeMonitored($jobClass)
            || $job instanceof \Illuminate\Events\CallQueuedListener) {
            return;
        }

        $jobClass = is_string($job) ? $job : get_class($job);
        /** @var string $jobQueue */
        $jobQueue = $job?->queue ?? trim(\Illuminate\Support\Facades\Queue::connection($jobConnection)->getQueue(null), '/');
//        $jobsChain = array_map(function ($item) {return get_class(unserialize($item));}, $job?->chained ?? []);

        $now = Carbon::now();

        $jobsRepository = app(QueueMonitorJobsRepository::class);
        $repository = self::getRepository();
        $repository->addQueued([
            'job_id' => $jobId,
            'queue_monitor_job_id' => $jobsRepository->firstOrCreate($jobClass),
            'queue' => $jobQueue,
            'connection' => $jobConnection,
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
     * @return void
     * @throws \ReflectionException
     *
     */
    protected static function jobStarted(Job $job): void
    {
        if (!self::shouldBeMonitored($job)) {
            return;
        }
        $now = Carbon::now();

        $jobsRepository = app(QueueMonitorJobsRepository::class);
        $repository = self::getRepository();

        /** @noinspection PhpUndefinedMethodInspection */
        $repository->updateOrCreateStarted([
            'job_id' => self::getJobId($job),
            'attempt' => $job->attempts(), //TODO: check! works with $job->attempts() - 1 only
            'queue_monitor_job_id' => $jobsRepository->firstOrCreate($job->resolveName()),
            'queue' => $job->getQueue(),
            'connection' => $job->getConnectionName(),
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
     * @return void
     * @throws \ReflectionException
     *
     */
    protected static function jobFinished(Job $job, bool $failed = false, ?\Throwable $exception = null): void
    {

        if (!self::shouldBeMonitored($job)) {
            return;
        }

        $repository = self::getRepository();

        /** @var QueueMonitorModel $monitor */
        $monitor = $repository->findByOrderBy('job_id', self::getJobId($job), ['*'], 'started_at');
        if (null === $monitor) {
            return;
        }

        $now = Carbon::now();

        if ($startedAt = $monitor->getStartedAtExact()) {
            $timeElapsed = (float)$startedAt->diffInSeconds($now) + $startedAt->diff($now)->f;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $resolvedJob = $job->resolveName();

        /** @noinspection PhpUndefinedMethodInspection */
        if (null === $exception && false === $resolvedJob::keepMonitorOnSuccess()) {
            $repository->deleteOne($monitor);

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
                'exception' => mb_strcut((string)$exception, 0, config('queue-monitor.db.max_length_exception', 4294967295)),
                'exception_class' => get_class($exception),
                'exception_message' => mb_strcut($exception->getMessage(), 0, config('queue-monitor.db.max_length_exception_message', 65535)),
            ];
        }

        $repository->updateFinished($monitor, $attributes);
    }

    /**
     * Determine weather the Job should be monitored, default true.
     *
     * @param Job|string $job
     *
     * @return bool
     * @throws \ReflectionException
     *
     */
    public static function shouldBeMonitored(Job|string $job): bool
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return match (true) {
            is_string($job) =>
                in_array(IsMonitored::class, array_keys((new \ReflectionClass($job))->getTraits()))
                || (new \ReflectionClass($job))->getParentClass()
                && in_array(IsMonitored::class, array_keys((new \ReflectionClass($job))->getParentClass()->getTraits())),
            default => array_key_exists(IsMonitored::class, ClassUses::classUsesRecursive(
                $job->resolveName()
            ))
        };
    }
}
