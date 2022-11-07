<?php

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Models\Job;
use xmlshop\QueueMonitor\Models\Monitor;

class QueuedJobsRepository extends BaseRepository
{

    public function getModelName(): string
    {
        return Monitor::class;
    }

    /**
     * @param string $date_from
     * @param string $date_to
     * @return Job[]|Builder[]|Collection
     */
    public function getJobsStatistic(string $date_from, string $date_to): Collection|array
    {
        return Job::select(['name', 'name_with_namespace'])
            ->selectRaw("@datefrom:=?", [$date_from])
            ->selectRaw("@dateto:=?", [$date_to])
            ->withCount([
                'assignedQueueMonitor as queued' => function (Builder $q) {
                    $q->whereBetween('queued_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'assignedQueueMonitor as started' => function (Builder $q) {
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')]);
                },
                'assignedQueueMonitor as finished_success' => function (Builder $q) {
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(0);
                },
                'assignedQueueMonitor as hanged_on' => function (Builder $q) {
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNull('finished_at');
                },
                'assignedQueueMonitor as finished_failed' => function (Builder $q) {
                    $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                        ->whereNotNull('finished_at')
                        ->whereFailed(1);
                }])
            ->withAvg(['assignedQueueMonitor as avg_exec_time' => function (Builder $q) {
                $q->whereBetween('started_at', [DB::raw('@datefrom'), DB::raw('@dateto')])
                    ->whereNotNull('finished_at')
                    ->whereFailed(0);
            }], 'time_elapsed')
            ->get();
    }
}
