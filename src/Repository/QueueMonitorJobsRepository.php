<?php

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Models\QueueMonitorJobModel;

class QueueMonitorJobsRepository extends BaseRepository
{

    public function getModelName(): string
    {
        return QueueMonitorJobModel::class;
    }

    /**
     * @param string $name_with_namespace
     * @return void
     */
    public function firstOrCreate(string $name_with_namespace)
    {
        return $this->model::query()
            ->firstOrCreate(
                [
                    'name_with_namespace' => $name_with_namespace,
                ],
                [
                    'name' => QueueMonitorJobModel::getBasename($name_with_namespace),
                    'name_with_namespace' => $name_with_namespace
                ],
            )->id;

    }

    /**
     * @param string $date_from
     * @param string $date_to
     * @return QueueMonitorJobModel[]|Builder[]|Collection
     */
    public function getJobsStatistic(string $date_from, string $date_to): Collection|array
    {
        /** @noinspection UnknownColumnInspection */
        return QueueMonitorJobModel::select(['name', 'name_with_namespace'])
            ->selectRaw("@datefrom:=?", [$date_from])
            ->selectRaw("@dateto:=?", [$date_to])
            ->withCount([
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
                }])
            ->withAvg(['assignedQueueMonitor as avg_exec_time' => function (Builder $q) {
                /** @noinspection UnknownColumnInspection */
                $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                    ->whereNotNull('finished_at')
                    ->whereFailed(0);
            }], 'time_elapsed')
            ->get();
    }
}
