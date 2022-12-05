<?php

namespace xmlshop\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use xmlshop\QueueMonitor\Controllers\Payloads\Metric;
use xmlshop\QueueMonitor\Controllers\Payloads\Metrics;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueRepository;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class ShowQueueMonitorController
{
    private array $filter_min_max_ids;

    /**
     * @param Request $request
     * @param QueueMonitorJobsRepository $jobsRepository
     * @param QueueMonitorQueueRepository $queueRepository
     *
     * @return Application|Factory|View
     */
    public function __invoke(Request $request, QueueMonitorJobsRepository $jobsRepository, QueueMonitorQueueRepository $queueRepository)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'pending', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string'],
            'job' => ['nullable', 'string'],
            'df' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'dt' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
            'job' => $data['job'] ?? 'all',
            'df' => $data['df'] ?? Carbon::now()->subHours(3)->toDateTimeLocalString(),
            'dt' => $data['dt'] ?? Carbon::now()->toDateTimeLocalString(),
        ];

        /** @noinspection UnknownColumnInspection */
        $jobs = QueueMonitorService::getModel()
            ->setConnection(config('queue-monitor.db.connection'))
            ->newQuery()
            ->select([config('queue-monitor.db.table.monitor') . '.*', 'mj.name_with_namespace as name', 'mq.queue_name as queue', 'mh.name as host'])
            ->where(function ($query) use ($filters) {
                /** @noinspection UnknownColumnInspection */
                $query->whereBetween(config('queue-monitor.db.table.monitor') . '.queued_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween(config('queue-monitor.db.table.monitor') . '.started_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween(config('queue-monitor.db.table.monitor') . '.finished_at', [$filters['df'], $filters['dt']]);
            })
            ->join(config('queue-monitor.db.table.monitor_jobs') . ' as mj', fn (JoinClause $join) => $join
                ->on(config('queue-monitor.db.table.monitor') . '.queue_monitor_job_id', '=', 'mj.id')
            )
            ->join(config('queue-monitor.db.table.monitor_hosts') . ' as mh', fn (JoinClause $join) => $join
                ->on(config('queue-monitor.db.table.monitor') . '.host_id', '=', 'mh.id')
            )
            ->join(config('queue-monitor.db.table.monitor_queues') . ' as mq', fn (JoinClause $join) => $join
                ->on(config('queue-monitor.db.table.monitor') . '.queue_id', '=', 'mq.id')
            )
            ->when(($type = $filters['type']) && 'all' !== $type, static function (Builder $builder) use ($type) {
                switch ($type) {
                    case 'pending':
                        /** @noinspection UnknownColumnInspection */
                        $builder->whereNotNull('queued_at')->whereNull(['started_at', 'finished_at']);
                        break;

                    case 'running':
                        /** @noinspection UnknownColumnInspection */
                        $builder->whereNotNull('started_at')->whereNull('finished_at');
                        break;

                    case 'failed':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', 1)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', 0)->whereNotNull('finished_at');
                        break;
                }
            })
            ->when(($queue = $filters['queue']) && 'all' !== $queue, static function (Builder $builder) use ($queue) {
                /** @noinspection UnknownColumnInspection */
                $builder->where('queue_id', $queue);
            })
            ->when(($monitor_job_id = $filters['job']) && 'all' !== $monitor_job_id, static function (Builder $builder) use ($monitor_job_id) {
                /** @noinspection UnknownColumnInspection */
                $builder->where('queue_monitor_job_id', $monitor_job_id);
            })
            ->orderByDesc('started_at')
            ->orderByDesc('queued_at')
            ->orderByDesc('finished_at')
//            ->dd()
            ->paginate(
                config('queue-monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );

        /** @noinspection UnknownColumnInspection */
        $queues = $queueRepository->select(['id', 'queue_name'])->toArray();

        /** @var \Illuminate\Database\Eloquent\Collection $jobs_list */
        $jobs_list = $jobsRepository->getCollection(['id', 'name'])->toArray();

        $metrics = null;

        if (config('queue-monitor.ui.show_metrics')) {
            $metrics = $this->collectMetrics();
        }
        $summary = null;
        $job_metrics = null;
        if (config('queue-monitor.ui.show_summary') && is_array(config('queue-monitor.ui.summary_conf'))) {
            $summary = $this->collectSummary($filters);
            if ('all' !== $filters['job']) {
                $job_metrics = $this->collectJobMetrics($filters['job'], $filters);
            }
        }

        return view('queue-monitor::jobs/index', [
            'jobs' => $jobs,
            'jobs_list' => $jobs_list,
            'filters' => $filters,
            'queues' => $queues,
            'metrics' => $metrics,
            'summary' => $summary,
            'job_metrics' => $job_metrics,
        ]);
    }

    public function collectMetrics(): Metrics
    {
        $timeFrame = config('queue-monitor.ui.metrics_time_frame') ?? 2;

        $metrics = new Metrics();

        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(time_elapsed) as total_time_elapsed'),
            DB::raw('AVG(time_elapsed) as average_time_elapsed'),
        ];

        /** @noinspection UnknownColumnInspection */
        $aggregatedInfo = QueueMonitorService::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame))
            ->first();

        /** @noinspection UnknownColumnInspection */
        $aggregatedComparisonInfo = QueueMonitorService::getModel()
            ->newQuery()
            ->select($aggregationColumns)
            ->where('started_at', '>=', Carbon::now()->subDays($timeFrame * 2))
            ->where('started_at', '<=', Carbon::now()->subDays($timeFrame))
            ->first();

        if (null === $aggregatedInfo || null === $aggregatedComparisonInfo) {
            return $metrics;
        }

        return $metrics
            ->push(
                new Metric('Total Jobs Executed', $aggregatedInfo->count ?? 0, $aggregatedComparisonInfo->count, '%d')
            )
            ->push(
                new Metric('Total Execution Time', $aggregatedInfo->total_time_elapsed ?? 0, $aggregatedComparisonInfo->total_time_elapsed, '%ds')
            )
            ->push(
                new Metric('Average Execution Time', $aggregatedInfo->average_time_elapsed ?? 0, $aggregatedComparisonInfo->average_time_elapsed, '%0.2fs')
            );
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private function collectSummary(array $filters): array
    {
        /** @var Builder $builder */
        $builder = app(\Illuminate\Database\Query\Builder::class);
        /** @noinspection UnknownColumnInspection */
        $aggregatedComparisonInfo = $builder;

        $subSelect = null;
        foreach (config('queue-monitor.ui.summary_conf') as $status) {
            switch ($status) {
                case 'running':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = QueueMonitorModel::query()
                        ->selectRaw('COUNT(1)')
                        ->whereBetween('started_at', [$filters['df'], $filters['dt']])
                        ->whereNull(['finished_at']);
                    break;
                case 'pending':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = QueueMonitorModel::query()
                        ->selectRaw('COUNT(1)')
                        ->whereBetween('queued_at', [$filters['df'], $filters['dt']])
                        ->whereNull(['started_at', 'finished_at']);
                    break;
                case 'succeeded':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = QueueMonitorModel::query()
                        ->selectRaw('COUNT(1)')
                        ->where(function ($query) use ($filters) {
                            $query->whereBetween('started_at', [$filters['df'], $filters['dt']])
                                ->orWhereBetween('finished_at', [$filters['df'], $filters['dt']]);
                        })
                        ->whereNotNull(['started_at', 'finished_at'])
                        ->where('failed', '=', 0);
                    break;
                case 'failed':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = QueueMonitorModel::query()
                        ->selectRaw('COUNT(1)')
                        ->whereNotNull(['started_at', 'finished_at'])
                        ->where(function ($query) use ($filters) {
                            $query->whereBetween('started_at', [$filters['df'], $filters['dt']])
                                ->orWhereBetween('finished_at', [$filters['df'], $filters['dt']]);
                        })
                        ->where('failed', '=', 1);
                    break;
            }
            if (null !== $subSelect) {
                $subSelect
                    ->when(($queue = $filters['queue']) && 'all' !== $queue, static function ($builder) use ($queue) {
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('queue_id', $queue);
                    })
                    ->when(($monitor_job_id = $filters['job']) && 'all' !== $monitor_job_id, static function ($builder) use ($monitor_job_id) {
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('queue_monitor_job_id', $monitor_job_id);
                    })
                    ->when(($type = $filters['type']) && 'all' !== $type, static function ($builder) use ($type) {
                        switch ($type) {
                            case 'pending':
                                /** @noinspection UnknownColumnInspection */
                                $builder->whereNotNull('queued_at')->whereNull(['started_at', 'finished_at']);
                                break;

                            case 'running':
                                /** @noinspection UnknownColumnInspection */
                                $builder->whereNotNull('started_at')->whereNull('finished_at');
                                break;

                            case 'failed':
                                /** @noinspection UnknownColumnInspection */
                                $builder->where('failed', 1)->whereNotNull('finished_at');
                                break;

                            case 'succeeded':
                                /** @noinspection UnknownColumnInspection */
                                $builder->where('failed', 0)->whereNotNull('finished_at');
                                break;
                        }
                    });

                $aggregatedComparisonInfo
                    ->selectSub($subSelect, $status);
            }
        }
//        $aggregatedComparisonInfo->dd();
        return collect($aggregatedComparisonInfo->first())->toArray();
    }

    private function collectJobMetrics(int $job_id, $filters)
    {
        /** @var Builder $builder */
        $builder = app(\Illuminate\Database\Query\Builder::class);

        /** @noinspection UnknownColumnInspection */
        $subs = [
            'pending_time' => QueueMonitorModel::query()
                ->selectRaw('AVG(time_pending_elapsed)')
                ->where('queue_monitor_job_id', '=', $job_id)
                ->whereNotNull(['queued_at', 'started_at'])
                ->where(function ($query) use ($filters) {
                    $query->whereBetween('queued_at', [$filters['df'], $filters['dt']])
                        ->orWhereBetween('started_at', [$filters['df'], $filters['dt']]);
                }),
            'execution_time' => QueueMonitorModel::query()
                ->selectRaw('AVG(time_elapsed)')
                ->where('queue_monitor_job_id', '=', $job_id)
                ->whereNotNull(['started_at', 'finished_at'])
                ->where('failed', '=', 0)
                ->where(function ($query) use ($filters) {
                    $query->whereBetween('started_at', [$filters['df'], $filters['dt']])
                        ->orWhereBetween('finished_at', [$filters['df'], $filters['dt']]);
                }),
        ];

        foreach ($subs as $key => $sub) {
            $builder->selectSub($sub, $key);
        }

        return collect($builder->first())->toArray();
    }
}
