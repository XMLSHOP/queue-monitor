<?php

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Class Job.
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 *
 * @method static Builder|QueueMonitorHostModel newModelQuery()
 * @method static Builder|QueueMonitorHostModel newQuery()
 * @method static Builder|QueueMonitorHostModel query()
 * @method static Builder|QueueMonitorHostModel select()
 * @method static Builder|QueueMonitorHostModel whereName()
 * @method static integer insert(array)
 */
class QueueMonitorHostModel extends Model
{
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('queue-monitor.db.table.monitor_hosts'));

        if ($connection = config('queue-monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    protected $appends = ['resource_url'];

    public function assignedQueueMonitor(): HasMany
    {
        return $this->hasMany(QueueMonitorModel::class, 'host_id');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/jobs/' . $this->getKey());
    }
}
