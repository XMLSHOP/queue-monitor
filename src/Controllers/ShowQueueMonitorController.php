<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class ShowQueueMonitorController
{
    public function __construct(private QueueMonitorService $queueMonitorService)
    {
    }

    public function __invoke(
        Request $request,
        JobRepositoryInterface $jobsRepository,
        QueueRepositoryInterface $queueRepository
    ): View {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'pending', 'running', 'failed', 'succeeded'])],
            'queue' => ['nullable', 'string'],
            'job' => ['nullable', 'string'],
            'df' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/'],
            'dt' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'queue' => $data['queue'] ?? 'all',
            'job' => $data['job'] ?? 'all',
            'df' => $data['df'] ?? Carbon::now()->subHours(3)->toDateTimeLocalString(),
            'dt' => $data['dt'] ?? Carbon::now()->toDateTimeLocalString(),
        ];

        /** @noinspection UnknownColumnInspection */
        $jobs = $this->queueMonitorService
            ->model
            ->setConnection(config('monitor.db.connection'))
            ->newQuery()
            ->select([config('monitor.db.table.monitor_queue') . '.*', 'mj.name_with_namespace as name', 'mq.queue_name as queue', 'mh.name as host'])
            ->where(function ($query) use ($filters) {
                /** @noinspection UnknownColumnInspection */
                $query->whereBetween(config('monitor.db.table.monitor_queue') . '.queued_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween(config('monitor.db.table.monitor_queue') . '.started_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween(config('monitor.db.table.monitor_queue') . '.finished_at', [$filters['df'], $filters['dt']]);
            })
            ->join(config('monitor.db.table.jobs') . ' as mj', fn (JoinClause $join) => $join
                ->on(config('monitor.db.table.monitor_queue') . '.queue_monitor_job_id', '=', 'mj.id')
            )
            ->join(config('monitor.db.table.hosts') . ' as mh', fn (JoinClause $join) => $join
                ->on(config('monitor.db.table.monitor_queue') . '.host_id', '=', 'mh.id')
            )
            ->join(config('monitor.db.table.queues') . ' as mq', fn (JoinClause $join) => $join
                ->on(config('monitor.db.table.monitor_queue') . '.queue_id', '=', 'mq.id')
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
            ->paginate(
                config('monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );

        /** @noinspection UnknownColumnInspection */
        $queues = $queueRepository->select(['id', 'queue_name'])->toArray();

        /** @var Collection $jobs_list */
        $jobs_list = $jobsRepository->getCollection(['id', 'name'])->toArray();

        $summary = null;
        $job_metrics = null;
        if (config('monitor.ui.summaries.queue.show') && is_array(config('monitor.ui.summaries.queue.conf'))) {
            $summary = $this->collectSummary($filters);
            if ('all' !== $filters['job']) {
                $job_metrics = $this->collectJobMetrics((int)$filters['job'], $filters);
            }
        }

        return view('monitor::jobs.index', [
            'jobs' => $jobs,
            'jobs_list' => $jobs_list,
            'filters' => $filters,
            'queues' => $queues,
            'summary' => $summary,
            'job_metrics' => $job_metrics ?? [],
        ]);
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
        foreach (config('monitor.ui.summaries.queue.conf') as $status) {
            switch ($status) {
                case 'running':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = MonitorQueue::query()
                        ->selectRaw('COUNT(1)')
                        ->whereBetween('started_at', [$filters['df'], $filters['dt']])
                        ->whereNull(['finished_at']);
                    break;
                case 'pending':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = MonitorQueue::query()
                        ->selectRaw('COUNT(1)')
                        ->whereBetween('queued_at', [$filters['df'], $filters['dt']])
                        ->whereNull(['started_at', 'finished_at']);
                    break;
                case 'succeeded':
                    /** @noinspection UnknownColumnInspection */
                    $subSelect = MonitorQueue::query()
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
                    $subSelect = MonitorQueue::query()
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

        return collect($aggregatedComparisonInfo->first())->toArray();
    }

    private function collectJobMetrics(int $job_id, $filters)
    {
        /** @var Builder $builder */
        $builder = app(\Illuminate\Database\Query\Builder::class);

        /** @noinspection UnknownColumnInspection */
        $subs = [
            'pending_time' => MonitorQueue::query()
                ->selectRaw('AVG(time_pending_elapsed)')
                ->where('queue_monitor_job_id', '=', $job_id)
                ->whereNotNull(['queued_at', 'started_at'])
                ->where(function ($query) use ($filters) {
                    $query->whereBetween('queued_at', [$filters['df'], $filters['dt']])
                        ->orWhereBetween('started_at', [$filters['df'], $filters['dt']]);
                }),
            'execution_time' => MonitorQueue::query()
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
