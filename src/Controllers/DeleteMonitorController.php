<?php

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;

class DeleteMonitorController
{
    public function __invoke(Request $request, QueueMonitorModel $monitor): RedirectResponse
    {
        $monitor->delete();

        return redirect()->route('queue-monitor::index');
    }
}
