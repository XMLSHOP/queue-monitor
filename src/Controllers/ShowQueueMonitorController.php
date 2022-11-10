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
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;
use xmlshop\QueueMonitor\Services\QueueMonitorService;
use xmlshop\QueueMonitor\Controllers\Payloads\Metric;
use xmlshop\QueueMonitor\Controllers\Payloads\Metrics;

class ShowQueueMonitorController
{
    /**
     * @param Request $request
     * @param QueueMonitorJobsRepository $jobsRepository
     * @return Application|Factory|View
     */
    public function __invoke(Request $request, QueueMonitorJobsRepository $jobsRepository)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'pending', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string'],
            'job'  => ['nullable', 'integer'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
            'job' => $data['job'] ?? 'all',
        ];

        $jobs = QueueMonitorService::getModel()
            ->setConnection(config('queue-monitor.connection'))
            ->newQuery()
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
                $builder->where('queue', $queue);
            })
            ->when(($monitor_job_id = $filters['job']) && 'all' !== $monitor_job_id, static function (Builder $builder) use ($monitor_job_id) {
                /** @noinspection UnknownColumnInspection */
                $builder->where('queue_monitor_job_id', $monitor_job_id);
            })
            ->join(config('queue-monitor.table.monitor_jobs') . ' as mj', fn(JoinClause $join) => $join
                ->on(config('queue-monitor.table.monitor') . '.queue_monitor_job_id', '=', 'mj.id')
            )
            ->select([config('queue-monitor.table.monitor') . '.*', 'mj.name_with_namespace as name'])
            ->ordered()
            ->paginate(
                config('queue-monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );

        /** @noinspection UnknownColumnInspection */
        $queues = QueueMonitorService::getModel()
            ->newQuery()
            ->select('queue')
            ->groupBy('queue')
            ->get()
            ->map(function (QueueMonitorModel $monitor) {
                return $monitor->queue;
            })
            ->toArray();

        /** @var \Illuminate\Database\Eloquent\Collection $jobs_list */
        $jobs_list = $jobsRepository->getCollection(['id', 'name'])->toArray();

        $metrics = null;

        if (config('queue-monitor.ui.show_metrics')) {
            $metrics = $this->collectMetrics();
        }

        return view('queue-monitor::jobs', [
            'jobs' => $jobs,
            'jobs_list' => $jobs_list,
            'filters' => $filters,
            'queues' => $queues,
            'metrics' => $metrics,
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
}
