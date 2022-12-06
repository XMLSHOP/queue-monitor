<?php

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $queue_name
 * @property string|null $connection_name
 * @property string|null $queue_name_started
 * @property string|null $connection_name_started
 * @property string|null $alert_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|QueueMonitorQueueModel newModelQuery()
 * @method static Builder|QueueMonitorQueueModel newQuery()
 * @method static Builder|QueueMonitorQueueModel query()
 * @method static Builder|QueueMonitorQueueModel select()
 * @method static Builder|QueueMonitorQueueModel whereQueueName()
 * @method static integer insert()
 */
class QueueMonitorQueueModel extends Model
{
    protected $guarded = ['id'];

    public $timestamps = true;

    /**
     * @var string[]
     */
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

        $this->setTable(config('monitor.db.table.queues'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    protected $appends = ['resource_url'];

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/queues/' . $this->getKey());
    }
}
