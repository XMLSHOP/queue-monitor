<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\RedirectResponse;
use xmlshop\QueueMonitor\Models\MonitorQueue;

class DeleteMonitorController
{
    public function __invoke(MonitorQueue $monitor): RedirectResponse
    {
        $monitor->delete();

        return redirect()->route('monitor::index');
    }
}
