<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Traits\Uuids;

/**
 * @property string $uuid
 * @property int $queue_id
 * @property Carbon|null $created_at
 */
class QueueSize extends Model
{
    use Uuids;

    protected $primaryKey = 'uuid';

    protected $guarded = ['uuid'];

    protected $dates = ['created_at'];

    public $timestamps = false;

    protected $appends = ['resource_url'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.queues_sizes'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function queues(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

}
