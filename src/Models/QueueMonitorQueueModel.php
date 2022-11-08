<?php

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $queue_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|QueueMonitorQueueModel newModelQuery()
 * @method static Builder|QueueMonitorQueueModel newQuery()
 * @method static Builder|QueueMonitorQueueModel query()
 * @method static Builder|QueueMonitorQueueModel select()
 * @method static Builder|QueueMonitorQueueModel whereQueueName()
 * @method static integer insert()
 */
class QueueMonitorQueueModel extends Model
{
    protected $connection = 'logs';

    protected $guarded = ['id'];

    public $timestamps = true;
    
    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/queues/'.$this->getKey());
    }
}
