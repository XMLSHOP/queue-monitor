<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use xmlshop\QueueMonitor\Models\QueueSize;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueSizeRepositoryInterface;

class QueueSizeRepository extends BaseRepository implements QueueSizeRepositoryInterface
{
    public function __construct(protected QueueSize $model)
    {
    }

    public function bulkInsert(array $data): bool
    {
        return $this->model->newQuery()->insert($data);
    }

    public function getDataSegment(string $from, string $to, ?array $queues = null): Builder
    {
        /** @noinspection UnknownColumnInspection */
        return $this->model
            ->newQuery()
            ->from(config('monitor.db.table.queues_sizes'), 'qs')
            ->select('qs.created_at')
            ->selectRaw('GROUP_CONCAT(CONCAT(q.connection_name, \':\', q.queue_name)) as queue_names')
            ->selectRaw('GROUP_CONCAT(qs.size) as sizes')
            ->whereBetween('qs.created_at', [$from, $to])
            ->join(config('monitor.db.table.queues').' as q', 'q.id', '=', 'qs.queue_id')
            ->when(null !== $queues, function (\Illuminate\Database\Eloquent\Builder $query) use ($queues) {
                $query->whereRaw(
                    'CONCAT(q.connection_name, \':\', q.queue_name) IN ('.
                    implode(',', array_map(function ($item) {
                        return "\"" . $item . "\"";
                    }, $queues))
                    .')'
                );
            })
            ->groupBy('qs.created_at')
            ->orderBy('qs.created_at');
    }

    public function purge(int $days): void
    {
        /** @noinspection UnknownColumnInspection */
        $this->model
            ->newQuery()
            ->where('created_at','<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
