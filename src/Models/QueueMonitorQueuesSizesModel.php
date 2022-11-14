<?php

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $queue_id
 * @property Carbon|null $created_at
 * @method static Builder|QueueMonitorQueuesSizesModel newModelQuery()
 * @method static Builder|QueueMonitorQueuesSizesModel newQuery()
 * @method static Builder|QueueMonitorQueuesSizesModel query()
 * @method static Builder|QueueMonitorQueuesSizesModel select()
 * @method static integer insert(array)
 */
class QueueMonitorQueuesSizesModel extends Model
{
    protected $guarded = ['id'];

    protected $dates = ['created_at'];

    public $timestamps = false;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('queue-monitor.db.table.monitor_queues_sizes'));

        if ($connection = config('queue-monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }
    
    protected $appends = ['resource_url'];

    public function queues(): BelongsTo
    {
        return $this->belongsTo(QueueMonitorQueueModel::class);
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/monitor-queues-sizes/'.$this->getKey());
    }
}
