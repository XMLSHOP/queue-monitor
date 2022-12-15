<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\RedirectResponse;
use xmlshop\QueueMonitor\Models\MonitorQueue;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class PurgeMonitorsController
{
    public function __invoke(QueueMonitorService $queueMonitorService): RedirectResponse
    {
        $queueMonitorService->model->newQuery()->each(function (MonitorQueue $monitor) {
            $monitor->delete();
        }, 200);

        return redirect()->route('monitor::index');
    }
}
