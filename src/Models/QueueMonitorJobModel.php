<?php

namespace xmlshop\QueueMonitor\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Job.
 *
 * @property int $id
 * @property string $name
 * @property string $name_with_namespace
 * @property Carbon|null $updated_at
 * @property Carbon|null $created_at
 *
 * @method static Builder|QueueMonitorJobModel newModelQuery()
 * @method static Builder|QueueMonitorJobModel newQuery()
 * @method static Builder|QueueMonitorJobModel query()
 * @method static Builder|QueueMonitorJobModel select()
 * @method static Builder|QueueMonitorJobModel whereNameWithNamespace()
 * @method static Builder|QueueMonitorJobModel whereName()
 * @method static integer insert(array)
 */
class QueueMonitorJobModel extends Model
{
    protected $connection = 'logs';
    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['resource_url'];

    public function assignedQueueMonitor(): HasMany
    {
        return $this->hasMany(QueueMonitorModel::class, 'name', 'name_with_namespace');
    }

    /* ************************ ACCESSOR ************************* */

    public function getResourceUrlAttribute()
    {
        return url('/admin/jobs/' . $this->getKey());
    }
}
