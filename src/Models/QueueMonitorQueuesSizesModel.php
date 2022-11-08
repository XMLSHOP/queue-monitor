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
 * @property Carbon|null $updated_at
 * @method static Builder|QueueMonitorQueuesSizesModel newModelQuery()
 * @method static Builder|QueueMonitorQueuesSizesModel newQuery()
 * @method static Builder|QueueMonitorQueuesSizesModel query()
 * @method static Builder|QueueMonitorQueuesSizesModel select()
 * @method static integer insert(array)
 */
class QueueMonitorQueuesSizesModel extends Model
{
    protected $connection = 'logs';

    protected $guarded = ['id'];

    protected $dates = [];
    public $timestamps = false;
    
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
