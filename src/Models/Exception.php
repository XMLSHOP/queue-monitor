<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Traits\Uuids;

/**
 * @property string $uuid
 * @property string $entity
 * @property string|null $exception
 * @property string|null $exception_class
 * @property string|null $exception_message
 * @property Carbon|null $created_at
 */
class Exception extends Model
{
    use Uuids;

    public const ENTITY_SCHEDULER = 'scheduler';
    public const ENTITY_COMMAND = 'command';
    public const ENTITY_JOB = 'job';

    protected $primaryKey = 'uuid';

    protected $guarded = ['uuid'];

    protected $dates = ['created_at'];

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.exceptions'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }
}
