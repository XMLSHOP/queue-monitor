<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $command
 * @property string|null $class_with_namespace
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Command extends Model
{
    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = ['command', 'class_with_namespace'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('monitor.db.table.commands'));

        if ($connection = config('monitor.db.connection')) {
            $this->setConnection($connection);
        }
    }
}
