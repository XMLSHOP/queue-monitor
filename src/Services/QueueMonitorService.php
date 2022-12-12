<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Queue;
use xmlshop\QueueMonitor\Models\MonitorExceptionModel;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\Contracts\QueueMonitorRepositoryContract;
use xmlshop\QueueMonitor\Repository\QueueMonitorHostsRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class QueueMonitorService
{
    public static bool $loadMigrations = false;

    public function __construct(
        private QueueMonitorRepositoryContract $queueMonitorRepository,
        private QueueMonitorJobsRepository $jobsRepository,
        private QueueMonitorHostsRepository $hostsRepository,
        public QueueMonitorModel $model
    ) {
    }

    public function handleJobQueued(JobQueued $jobQueued): void
    {
        $this->jobQueued($jobQueued->id, $jobQueued->connectionName, $jobQueued->job);
    }

    public function handleJobProcessing(JobProcessing $jobProcessing): void
    {
        $this->jobStarted($jobProcessing->job);
    }

    public function handleJobProcessed(JobProcessed $jobProcessed): void
    {
        $this->jobFinished($jobProcessed->job);
    }

    public function handleJobFailed(JobFailed $jobFailed): void
    {
        $this->jobFinished($jobFailed->job, true, $jobFailed->exception);
    }

    public function handleJobExceptionOccurred(JobExceptionOccurred $jobException): void
    {
        $this->jobFinished($jobException->job, true, $jobException->exception);
    }

    public function getJobId(JobContract $job): string|int
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
     */
    protected function jobQueued(mixed $jobId, ?string $jobConnection, \Closure|ShouldQueue|string $job): void
    {
        if ((!$job instanceof JobContract && !$job instanceOf ShouldQueue)
            || !self::shouldBeMonitored($job)
        ) {
            return;
        }

        $jobClass = is_string($job) ? $job : get_class($job);

        /** @var string $jobQueue */
        $jobQueue = $job?->queue ?? trim(Queue::connection($jobConnection)->getQueue(null), '/');

        $this->queueMonitorRepository->addQueued([
            'job_id' => (string) $jobId,
            'queue_monitor_job_id' => $this->jobsRepository->firstOrCreate($jobClass),
            'queue' => $jobQueue,
            'host_id' => $this->hostsRepository->firstOrCreate(),
            'connection' => $jobConnection,
            'queued_at' => now(),
            'attempt' => 0,
        ]);
    }

    /**
     * Start Queue Monitoring for Job.
     */
    protected function jobStarted(JobContract $job): void
    {
        if (!self::shouldBeMonitored($job)) {
            return;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->queueMonitorRepository->updateOrCreateStarted([
            'job_id' => $this->getJobId($job),
            'attempt' => $job->attempts(),
            'queue_monitor_job_id' => $this->jobsRepository->firstOrCreate($job->resolveName()),
            'queue' => $job->getQueue(),
            'host_id' => $this->hostsRepository->firstOrCreate(),
            'connection' => $job->getConnectionName(),
            'started_at' => now(),
        ]);
    }

    /**
     * Finish Queue Monitoring for Job.
     */
    protected function jobFinished(JobContract $job, bool $failed = false, ?\Throwable $exception = null): void
    {
        if (!self::shouldBeMonitored($job)) {
            return;
        }

        $now = now();

        /** @var QueueMonitorModel $monitor */
        $monitor = $this->queueMonitorRepository->findByOrderBy('job_id', $this->getJobId($job), ['*'], 'started_at');

        if (!$monitor) {
            return;
        }

        if ($startedAt = $monitor->getStarted()) {
            $timeElapsed = (float) $startedAt->diffInSeconds($now) + $startedAt->diff($now)->f;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $resolvedJob = $job->resolveName();

        /** @noinspection PhpUndefinedMethodInspection */
        if (null === $exception && false === $resolvedJob::keepMonitorOnSuccess()) {
            $this->queueMonitorRepository->deleteOne($monitor);

            return;
        }

        $attributes = [
            'finished_at' => $now,
            'time_elapsed' => $timeElapsed ?? 0.0,
            'failed' => $failed,
        ];

        $monitor = $this->queueMonitorRepository->updateFinished($monitor, $attributes);

        if (null !== $exception) {
            $monitorException = MonitorExceptionModel::query()->create([
                'entity' => MonitorExceptionModel::ENTITY_JOB,
                'exception' => mb_strcut((string) $exception, 0, config('monitor.db.max_length_exception')),
                'exception_class' => get_class($exception),
                'exception_message' => mb_strcut($exception->getMessage(), 0, config('monitor.db.max_length_exception_message')),
                'created_at' => now()
            ]);

            $monitor->exception()->associate($monitorException);
            $monitor->save();
        }
    }

    /**
     * Determine weather the Job should be monitored, default true.
     */
    public static function shouldBeMonitored(JobContract|ShouldQueue $job): bool
    {
        $jobName = match (true) {
            $job instanceof ShouldQueue => get_class($job),
            default => $job->resolveName()
        };

        return array_key_exists(IsMonitored::class, ClassUses::classUsesRecursive($jobName));
    }
}
