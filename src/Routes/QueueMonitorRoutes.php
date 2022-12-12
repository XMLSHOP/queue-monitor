<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Routes;

use Closure;
use Illuminate\Routing\Router;

class QueueMonitorRoutes
{
    /**
     * Scaffold the Queue Monitor UI routes.
     */
    public function queueMonitor(): Closure
    {
        return function (array $options = []) {
            /** @var Router $this */
            $this->get('/', '\xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController')
                ->name('queue-monitor::jobs');

            /** @var Router $this */
            $this->get('queue-sizes', '\xmlshop\QueueMonitor\Controllers\QueueSizesChartsController')
                ->name('queue-monitor::queue-sizes');


            if (config('monitor.ui.allow_deletion')) {
                /** @var Router $this */
                $this->delete('monitors/{monitor}', '\xmlshop\QueueMonitor\Controllers\DeleteMonitorController')
                    ->name('queue-monitor::destroy');
            }

            if (config('monitor.ui.allow_purge')) {
                /** @var Router $this */
                $this->delete('purge', '\xmlshop\QueueMonitor\Controllers\PurgeMonitorsController')
                    ->name('queue-monitor::purge');
            }
        };
    }
}
