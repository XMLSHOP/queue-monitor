<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\RedirectResponse;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class PurgeMonitorsController
{
    public function __invoke(QueueMonitorService $queueMonitorService): RedirectResponse
    {
        $queueMonitorService->model->query()->each(function (QueueMonitorModel $monitor) {
            $monitor->delete();
        }, 200);

        return redirect()->route('monitor::index');
    }
}
