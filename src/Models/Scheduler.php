<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Traits\Uuids;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string|null $cron_expression
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Scheduler extends Model
{
    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = ['name', 'type', 'cron_expression'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.schedulers'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function monitorScheduler(): HasMany
    {
        return $this->hasMany(MonitorScheduler::class, 'scheduled_id');
    }
}
