<?php

namespace xmlshop\QueueMonitor\Routes;

use Closure;

class QueueMonitorRoutes
{
    /**
     * Scaffold the Queue Monitor UI routes.
     *
     * @return \Closure
     */
    public function queueMonitor(): Closure
    {
        return function (array $options = []) {
            /** @var \Illuminate\Routing\Router $this */
            $this->get('jobs', '\xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController')->name('queue-monitor::jobs');

            /** @var \Illuminate\Routing\Router $this */
            $this->get('queue-sizes', '\xmlshop\QueueMonitor\Controllers\QueueSizesChartsController')->name('queue-monitor::queue-sizes');


            if (config('queue-monitor.ui.allow_deletion')) {
                $this->delete('monitors/{monitor}', '\xmlshop\QueueMonitor\Controllers\DeleteMonitorController')->name('queue-monitor::destroy');
            }

            if (config('queue-monitor.ui.allow_purge')) {
                $this->delete('purge', '\xmlshop\QueueMonitor\Controllers\PurgeMonitorsController')->name('queue-monitor::purge');
            }
        };
    }
}
