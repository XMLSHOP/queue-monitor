<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $queue_name
 * @property string|null $connection_name
 * @property string|null $alert_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Queue extends Model
{
    protected $guarded = ['id'];

    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at',];

    protected $appends = ['resource_url'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.queues'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function getResourceUrlAttribute()
    {
        return url('/admin/queues/' . $this->getKey());
    }
}
