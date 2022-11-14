<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;


use Illuminate\Database\Eloquent\Builder;
use xmlshop\QueueMonitor\Models\QueueMonitorQueuesSizesModel;

class QueueMonitorQueueSizesRepository extends BaseRepository
{

    public function getModelName(): string
    {
        return QueueMonitorQueuesSizesModel::class;
    }

    /**
     * @param array $data
     * @return int
     */
    public function bulkInsert(array $data)
    {
        return $this->model::insert($data);
    }

    /**
     * @param string $from
     * @param string $to
     * @param array|null $queues
     * @return Builder|QueueMonitorQueuesSizesModel
     */
    public function getDataSegment(string $from, string $to, ?array $queues = null): Builder|QueueMonitorQueuesSizesModel
    {
//        $res =
        return
            $this->model::query()
            ->from(config('queue-monitor.db.table.monitor_queues_sizes'), 'qs')
            ->select('qs.created_at')
            ->selectRaw('GROUP_CONCAT(q.queue_name) as queue_names')
            ->selectRaw('GROUP_CONCAT(qs.size) as sizes')
            ->whereBetween('qs.created_at', [$from, $to])
            ->join(config('queue-monitor.db.table.monitor_queues').' as q', 'q.id', '=', 'qs.queue_id')
            ->when(null !== $queues, function ($query) use ($queues) {
                $query->whereIn('q.queue_name', $queues);
            })
            ->groupBy('qs.created_at')
            ->orderBy('qs.created_at');

//        $res->dd();
//        return $res;
    }
}
