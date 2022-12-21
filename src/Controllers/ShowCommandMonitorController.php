<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use xmlshop\QueueMonitor\Repository\Interfaces\CommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;


class ShowCommandMonitorController
{
    public function __construct(
        private MonitorCommandRepositoryInterface $monitorCommandRepository,
        private CommandRepositoryInterface $commandRepository)
    {
    }

    public function __invoke(
        Request $request
    ): View {
        $data = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['all', 'running', 'failed', 'succeeded'])],
            'command' => ['nullable', 'string'],
            'df' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'dt' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
        ]);

        $filters = [
            'type' => $data['type'] ?? 'all',
            'command' => $data['command'] ?? 'all',
            'df' => $data['df'] ?? Carbon::now()->subHours(3)->toDateTimeLocalString(),
            'dt' => $data['dt'] ?? Carbon::now()->toDateTimeLocalString(),
        ];

        return view('monitor::commands.index', [
            'commands' => $this->monitorCommandRepository->getList($request, $filters),
            'commands_list' => $this->commandRepository->getList('id'),
            'filters' => $filters,
        ]);
    }
}
