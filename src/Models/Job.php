<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use function last;

/**
 * @property int $id
 * @property string $name
 * @property string $name_with_namespace
 * @property int|null failures_amount_threshold
 * @property int|null pending_amount_threshold
 * @property double|null pending_time_threshold
 * @property double|null pending_time_to_previous_factor
 * @property double|null execution_time_to_previous_factor
 * @property bool ignore_all_besides_failures
 * @property bool ignore
 * @property int|null failures_amount
 * @property int|null pending_amount
 * @property double|null pending_time
 * @property double|null execution_time
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 */
class Job extends Model
{
    protected $guarded = ['id'];

    protected $fillable = ['name', 'name_with_namespace'];

    protected $dates = ['created_at', 'updated_at'];

    protected $appends = ['resource_url'];

    protected $casts = [
        'failures_amount_threshold' => 'int',
        'pending_amount_threshold' => 'int',
        'pending_time_threshold' => 'int',
        'pending_time_to_previous_factor' => 'double',
        'execution_time_to_previous_factor' => 'double',
        'ignore_all_besides_failures' => 'bool',
        'ignore' => 'bool',
        'failures_amount' => 'int',
        'pending_amount' => 'int',
        'pending_time' => 'double'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.jobs'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function monitorQueue(): HasMany
    {
        return $this->hasMany(MonitorQueue::class, 'queue_monitor_job_id');
    }

    public function getBasenameJob(): ?string
    {
        return !$this->name ? null : $this->getBasename($this->name);
    }

    public function getBasename(string $name): ?string
    {
        return last(explode('\\', $name));
    }

    public function getResourceUrlAttribute()
    {
        return url('/admin/jobs/' . $this->getKey());
    }

    public function getAlarmCheckers()
    {
        $params = [];

        foreach ([
                     'failures_amount_threshold',
                     'pending_amount_threshold',
                     'pending_time_threshold',
                     'pending_time_to_previous_factor',
                     'execution_time_to_previous_factor',
                 ] as $checker) {
            if (!empty($this->$checker)) {
                $params[$checker] = $this->$checker;
            }
        }

        return $params;
    }
}
