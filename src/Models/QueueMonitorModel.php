<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Traits\Uuids;

/**
 * @property string $uuid
 * @property string $job_id
 * @property int $queue_monitor_job_id
 * @property string|null $queue
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property float $time_pending_elapsed
 * @property Carbon|null $finished_at
 * @property float $time_elapsed
 * @property bool $failed
 * @property int $attempt
 * @property int|null $progress
 * @property MonitorExceptionModel|null $exception
 * @property string|null $data
 *
 * @method static Builder|QueueMonitorModel whereJob()
 * @method static Builder|QueueMonitorModel ordered()
 * @method static Builder|QueueMonitorModel lastHour()
 * @method static Builder|QueueMonitorModel today()
 * @method static Builder|QueueMonitorModel failed()
 * @method static Builder|QueueMonitorModel succeeded()
 */
class QueueMonitorModel extends Model
{
    use Uuids;

    protected $primaryKey = 'uuid';

    protected $guarded = ['uuid'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'failed' => 'bool',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'queued_at',
        'started_at',
        'finished_at',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.monitor_queue'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function exception(): BelongsTo
    {
        return $this->belongsTo(MonitorExceptionModel::class, 'exception_id', 'uuid');
    }

    /*
     *--------------------------------------------------------------------------
     * Scopes
     *--------------------------------------------------------------------------
     */

    public function scopeWhereJob(Builder $query, string|int $jobId): void
    {
        /** @noinspection UnknownColumnInspection */
        $query->where('job_id', $jobId);
    }

    public function scopeOrdered(Builder $query): void
    {
        /** @noinspection UnknownColumnInspection */
        $query
            ->orderBy('started_at', 'desc')
            ->orderBy('queued_at', 'desc');
    }

    public function scopeLastHour(Builder $query): void
    {
        /** @noinspection UnknownColumnInspection */
        $query->where('started_at', '>', Carbon::now()->subHours(1));
    }

    public function scopeToday(Builder $query): void
    {
        $query->whereRaw('DATE(started_at) = ?', [Carbon::now()->subHours(1)->format('Y-m-d')]);
    }

    public function scopeFailed(Builder $query): void
    {
        /** @noinspection UnknownColumnInspection */
        $query->where('failed', true);
    }

    public function scopeSucceeded(Builder $query): void
    {
        /** @noinspection UnknownColumnInspection */
        $query->where('failed', false);
    }

    /*
     *--------------------------------------------------------------------------
     * Methods
     *--------------------------------------------------------------------------
     */

    public function getQueued(): ?Carbon
    {
        if (null === $this->queued_at) {
            return null;
        }

        return Carbon::parse($this->queued_at);
    }

    public function getStarted(): ?Carbon
    {
        if (null === $this->started_at) {
            return null;
        }

        return Carbon::parse($this->started_at);
    }

    public function getFinished(): ?Carbon
    {
        if (null === $this->finished_at) {
            return null;
        }

        return Carbon::parse($this->finished_at);
    }

    /**
     * Get the estimated remaining seconds. This requires a job progress to be set.
     */
    public function getRemainingSeconds(Carbon $now = null): float
    {
        return $this->getRemainingInterval($now)->totalSeconds;
    }

    public function getRemainingInterval(Carbon $now = null): CarbonInterval
    {
        if (null === $now) {
            $now = Carbon::now();
        }

        if (!$this->progress || null === $this->started_at || $this->isFinished()) {
            return CarbonInterval::seconds(0);
        }

        if (0 === ($timeDiff = $now->getTimestamp() - $this->started_at->getTimestamp())) {
            return CarbonInterval::seconds(0);
        }

        return CarbonInterval::seconds(
            (100 - $this->progress) / ($this->progress / $timeDiff)
        )->cascade();
    }

    /**
     * Get the currently elapsed seconds.
     */
    public function getElapsedSeconds(Carbon $end = null): float
    {
        return $this->getElapsedInterval($end)->seconds;
    }

    public function getElapsedInterval(Carbon $end = null): CarbonInterval
    {
        if (null === $end) {
            $end = $this->getFinished() ?? $this->finished_at ?? Carbon::now();
        }

        $startedAt = $this->getStarted() ?? $this->started_at;

        if (null === $startedAt) {
            return CarbonInterval::seconds(0);
        }

        return $startedAt->diffAsCarbonInterval($end);
    }

    /**
     * Get any optional data that has been added to the monitor model within the job.
     */
    public function getData(): array
    {
        return json_decode($this->data, true) ?? [];
    }

    /**
     * Recreate the exception.
     *
     * @param bool $rescue Wrap the exception recreation to catch exceptions
     */
    public function getException(bool $rescue = true): ?\Throwable
    {
        if (null === $this->exception) {
            return null;
        }

        if (!$rescue) {
            return new $this->exception->exception_class($this->exception->exception_message);
        }

        try {
            return new $this->exception->exception_class($this->exception->exception_message);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function isPending(): bool
    {
        return !$this->hasFailed()
            && null !== $this->queued_at
            && null === $this->started_at
            && null === $this->finished_at;
    }

    public function isFinished(): bool
    {
        if ($this->hasFailed()) {
            return true;
        }

        return null !== $this->finished_at;
    }

    public function hasFailed(): bool
    {
        return true === $this->failed;
    }

    public function hasSucceeded(): bool
    {
        if (!$this->isFinished()) {
            return false;
        }

        return !$this->hasFailed();
    }
}
