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
 * @method static Builder|Queue newModelQuery()
 * @method static Builder|Queue newQuery()
 * @method static Builder|Queue query()
 * @method static Builder|Queue select()
 * @method static Builder|Queue whereQueueName()
 * @method static integer insert()
 */
class Queue extends Model
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
