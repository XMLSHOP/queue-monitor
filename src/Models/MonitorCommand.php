<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Traits\MonitorModel;
use xmlshop\QueueMonitor\Traits\Uuids;

/**
 * @property string $uuid
 * @property int $command_id
 * @property int $host_id
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property float $time_elapsed
 * @property bool $failed
 * @property string|null $exception_id
 * @property Exception|null $exception
 * @property int|null $use_memory_mb
 * @property float|null $use_cpu
 * @property Carbon|null $created_at
 */
class MonitorCommand extends Model
{
    use Uuids;
    use MonitorModel;

    protected $primaryKey = 'uuid';

    protected $guarded = ['uuid'];

    protected $dates = ['created_at', 'started_at', 'finished_at'];

    public $timestamps = false;

    public $with = ['command'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.monitor_command'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }

    public function exception(): BelongsTo
    {
        return $this->belongsTo(Exception::class, 'exception_id', 'uuid');
    }

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class, 'command_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class, 'host_id');
    }
}
