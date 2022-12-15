<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use xmlshop\QueueMonitor\Models\Job;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;

class JobRepository extends BaseRepository implements JobRepositoryInterface
{
    public function __construct(protected Job $model)
    {
    }

    public function firstOrCreate(string $name_with_namespace): Model
    {
        return $this->model->newQuery()->firstOrCreate([
            'name_with_namespace' => $name_with_namespace,
        ], [
            'name' => $this->model->getBasename($name_with_namespace),
        ]);
    }

    public function getJobsStatistic(string $date_from, string $date_to): Collection
    {
        /** @noinspection UnknownColumnInspection */
        return $this->model->newQuery()
            ->select(['name', 'name_with_namespace'])
            ->selectRaw('@datefrom:=?', [$date_from])
            ->selectRaw('@dateto:=?', [$date_to])
            ->withCount(relations: [
                'assignedQueueMonitor as queued' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('queued_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'assignedQueueMonitor as started' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'assignedQueueMonitor as finished_success' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(0);
                },
                'assignedQueueMonitor as hanged_on' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNull('finished_at');
                },
                'assignedQueueMonitor as finished_failed' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(1);
                }
            ])
            ->withAvg(relation: ['assignedQueueMonitor as avg_exec_time' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(0);
                }],
                column: 'time_elapsed'
            )
            ->get();
    }

    public function getJobsAlertInfo(int $period_seconds, int $offset_seconds): Collection
    {
        /** @noinspection UnknownColumnInspection */
        return $this->model->newQuery()
            ->select(['qmj.id', 'qmj.name'])
            ->selectRaw('SUM(qm.failed) as FailedCount')
            ->selectRaw('COUNT(1) - COUNT(qm.time_pending_elapsed) as PendingCount')
            ->selectRaw('AVG(time_pending_elapsed) as PendingAvg')
            ->selectRaw('AVG(time_elapsed) as ExecutingAvg')
            ->from(config('monitor.db.table.monitor_queue'), 'qm')
            ->join(
                config('monitor.db.table.jobs') . ' as qmj',
                'qmj.id',
                '=',
                'qm.queue_monitor_job_id'
            )
            ->whereRaw(
                'qm.queued_at BETWEEN (DATE_SUB(NOW(),INTERVAL ? SECOND)) AND (DATE_SUB(NOW(),INTERVAL ? SECOND))',
                [$period_seconds, $offset_seconds]
            )
            ->orWhereRaw(
                'qm.started_at BETWEEN (DATE_SUB(NOW(),INTERVAL ? SECOND)) AND (DATE_SUB(NOW(),INTERVAL ? SECOND))',
                [$period_seconds, $offset_seconds]
            )
            ->groupBy('qmj.id')
            ->orderBy('qmj.id')
            ->get();
    }
}
