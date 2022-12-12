<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\RedirectResponse;
use xmlshop\QueueMonitor\Models\QueueMonitorModel;

class DeleteMonitorController
{
    public function __invoke(QueueMonitorModel $monitor): RedirectResponse
    {
        $monitor->delete();

        return redirect()->route('monitor::index');
    }
}
