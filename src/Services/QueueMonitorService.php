<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Closure;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Queue;
use Throwable;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorQueueRepositoryInterface;
use xmlshop\QueueMonitor\Traits\IsMonitored;
use function last;

class QueueMonitorService
{
    public static bool $loadMigrations = false;

    public function __construct(
        private MonitorQueueRepositoryInterface $queueMonitorRepository,
        private JobRepositoryInterface $jobsRepository,
        private HostRepositoryInterface $hostsRepository,
        private ExceptionRepositoryInterface $exceptionRepository,
        public MonitorQueue $model
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
    protected function jobQueued(mixed $jobId, ?string $jobConnection, Closure|ShouldQueue|string $job): void
    {
        if ((!$job instanceof JobContract && !$job instanceof ShouldQueue)
            || !self::shouldBeMonitored($job)
        ) {
            return;
        }

        $jobClass = get_class($job); //TODO: look at it!

        /** @var string $jobQueue */
        $jobQueue = $job?->queue ?? last(explode('/', trim(Queue::connection($jobConnection ?? $job?->getConnectionName())->getQueue(null), '/')));

        $jobModel = $this->jobsRepository->firstOrCreate($jobClass);
        $hostModel = $this->hostsRepository->firstOrCreate();

        $this->queueMonitorRepository->addQueued([
            'job_id' => (string)$jobId,
            'queue_monitor_job_id' => $jobModel->id,
            'queue' => $jobQueue,
            'host_id' => $hostModel->id,
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

        $jobModel = $this->jobsRepository->firstOrCreate($job->resolveName());
        $hostModel = $this->hostsRepository->firstOrCreate();

        /** @noinspection PhpUndefinedMethodInspection */
        $this->queueMonitorRepository->updateOrCreateStarted([
            'job_id' => $this->getJobId($job),
            'attempt' => $job->attempts(),
            'queue_monitor_job_id' => $jobModel->id,
            'queue' => $job->getQueue(),
            'host_id' => $hostModel->id,
            'connection' => $job->getConnectionName(),
            'started_at' => now(),
        ]);
    }

    /**
     * Finish Queue Monitoring for Job.
     */
    protected function jobFinished(JobContract $job, bool $failed = false, ?Throwable $exception = null): void
    {
        if (!self::shouldBeMonitored($job)) {
            return;
        }

        $now = now();

        /** @var MonitorQueue $monitor */
        $monitor = $this->queueMonitorRepository
            ->findByOrderBy('job_id', $this->getJobId($job), ['*'], 'started_at');

        if (!$monitor) {
            return;
        }

        if ($startedAt = $monitor->getStarted()) {
            $timeElapsed = (float)$startedAt->diffInSeconds($now) + $startedAt->diff($now)->f;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $resolvedJob = $job->resolveName();

        /** @noinspection PhpUndefinedMethodInspection */
        if (null === $exception && false === $resolvedJob::keepMonitorOnSuccess()) {
            $this->queueMonitorRepository->deleteOne($monitor);

            return;
        }

        $monitor = $this->queueMonitorRepository->updateFinished($monitor, [
            'finished_at' => $now,
            'time_elapsed' => $timeElapsed ?? 0.0,
            'failed' => $failed,
        ]);

        if ($exception) {
            $monitorException = $this->exceptionRepository->createFromThrowable($exception);
            $monitor->exception()->associate($monitorException);
            $monitor->save();
        }
    }

    /**
     * Determine weather the Job should be monitored.
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
