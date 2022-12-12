<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $name_with_namespace
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 */
class QueueMonitorJobModel extends Model
{
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['resource_url'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.jobs'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    /**
     * Get the base class name of the job.
     */
    public function getBasenameJob(): ?string
    {
        if (null === $this->name) {
            return null;
        }

        return self::getBasename($this->name);
    }

    /**
     * Get the base class name, without namespace
     */
    public static function getBasename(string $name): ?string
    {
        return Arr::last(explode('\\', $name));
    }

    public function assignedQueueMonitor(): HasMany
    {
        return $this->hasMany(QueueMonitorModel::class, 'queue_monitor_job_id');
    }

    public function getResourceUrlAttribute()
    {
        return url('/admin/jobs/' . $this->getKey());
    }
}
