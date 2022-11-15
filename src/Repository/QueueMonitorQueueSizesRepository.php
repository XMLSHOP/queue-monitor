<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Carbon\Carbon;
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
        /** @noinspection UnknownColumnInspection */
        return
            $this->model::query()
            ->from(config('queue-monitor.db.table.monitor_queues_sizes'), 'qs')
            ->select('qs.created_at')
            ->selectRaw('GROUP_CONCAT(CONCAT(q.connection_name, \':\', q.queue_name)) as queue_names')
            ->selectRaw('GROUP_CONCAT(qs.size) as sizes')
            ->whereBetween('qs.created_at', [$from, $to])
            ->join(config('queue-monitor.db.table.monitor_queues').' as q', 'q.id', '=', 'qs.queue_id')
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

//        $res->dd();
//        return $res;
    }

    /**
     * @param int $days
     * @return void
     */
    public function purge(int $days): void
    {
        /** @noinspection UnknownColumnInspection */
        $this->model::query()
            ->where('created_at','<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
