<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;


class ShowCommandMonitorController
{
    public function __construct(private MonitorCommandRepository $repository)
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

        $summary = null;
        return view('monitor::commands/index', [
//            'jobs' => $jobs,
//            'jobs_list' => $jobs_list,
            'filters' => $filters,
//            'queues' => $queues,
//            'metrics' => $metrics,
            'summary' => $summary,
//            'job_metrics' => $job_metrics,
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
        foreach (config('monitor.ui.summary_conf') as $status) {
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

    private function collectEntitiesMetrics(int $entity_id, $filters)
    {
        return collect([])->toArray();
    }
}
