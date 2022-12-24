<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
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
                'monitorQueue as queued' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('queued_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'monitorQueue as started' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'monitorQueue as finished_success' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(0);
                },
                'monitorQueue as hanged_on' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNull('finished_at');
                },
                'monitorQueue as finished_failed' => function (Builder $q) {
                    /** @noinspection UnknownColumnInspection */
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(1);
                }
            ])
            ->withAvg(relation: ['monitorQueue as avg_exec_time' => function (Builder $q) {
                /** @noinspection UnknownColumnInspection */
                $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                    ->whereNotNull('finished_at')
                    ->whereFailed(0);
            }],
                column: 'time_elapsed'
            )
            ->get();
    }

    public function getJobsAlertInfo(int $period_seconds, int $offset_seconds, bool $with_checkers): Collection
    {
        /** @noinspection UnknownColumnInspection */
        return $this->model->newQuery()
            ->select(['id', 'name',])
            ->when($with_checkers, function (Builder $builder) {
                $builder->select([
                    'id',
                    'name',
                    'failures_amount_threshold',
                    'pending_amount_threshold',
                    'pending_time_threshold',
                    'pending_time_to_previous_factor',
                    'execution_time_to_previous_factor',
                    'ignore_all_besides_failures',
                    'ignore',
                ]);
            })
            ->selectRaw('SUM(failed) as failures_amount')
            ->selectRaw('COUNT(1) - COUNT(time_pending_elapsed) as pending_amount')
            ->selectRaw('AVG(time_pending_elapsed) as pending_time')
            ->selectRaw('AVG(time_elapsed) as execution_time')
            ->join(config('monitor.db.table.monitor_queue'),
                fn(JoinClause $join) => $join
                    ->on(config('monitor.db.table.monitor_queue') . '.queue_monitor_job_id',
                        '=',
                        'id')
                    ->where(fn(JoinClause $joinClause) => $joinClause
                        ->whereRaw(
                            'queued_at BETWEEN (DATE_SUB(NOW(),INTERVAL ? SECOND)) AND (DATE_SUB(NOW(),INTERVAL ? SECOND))',
                            [$period_seconds, $offset_seconds]
                        )
                        ->orWhereRaw(
                            'started_at BETWEEN (DATE_SUB(NOW(),INTERVAL ? SECOND)) AND (DATE_SUB(NOW(),INTERVAL ? SECOND))',
                            [$period_seconds, $offset_seconds]
                        )
                    )
            )
            ->groupBy('id')
            ->orderBy('id')
            ->get();
    }
}
