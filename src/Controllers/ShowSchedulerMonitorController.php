<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorSchedulerRepositoryInterface;
use xmlshop\QueueMonitor\Repository\SchedulerRepository;


class ShowSchedulerMonitorController
{
    public function __construct(
        private MonitorSchedulerRepositoryInterface $monitorSchedulerRepository,
        private SchedulerRepository $schedulerRepository)
    {
    }

    public function __invoke(
        Request $request
    ): View {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'running', 'failed', 'succeeded'])],
            'scheduler' => ['nullable', 'string'],
            'df' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'dt' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'scheduler' => $data['scheduler'] ?? 'all',
            'df' => $data['df'] ?? Carbon::now()->subHours(3)->toDateTimeLocalString(),
            'dt' => $data['dt'] ?? Carbon::now()->toDateTimeLocalString(),
        ];

        return view('monitor::schedulers.index', [
            'schedulers' => $this->monitorSchedulerRepository->getList($request, $filters),
            'schedulers_list' => $this->schedulerRepository->getList('id'),
            'filters' => $filters,
        ]);
    }
}
