<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 */
class Host extends Model
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

        $this->setTable(config('monitor.db.table.hosts'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function monitorScheduler(): HasMany
    {
        return $this->hasMany(MonitorScheduler::class, 'host_id');
    }

    public function monitorCommand(): HasMany
    {
        return $this->hasMany(MonitorCommand::class, 'host_id');
    }

    public function monitorQueue(): HasMany
    {
        return $this->hasMany(MonitorQueue::class, 'host_id');
    }

    public function getResourceUrlAttribute()
    {
        return url('/admin/hosts/' . $this->getKey());
    }
}
