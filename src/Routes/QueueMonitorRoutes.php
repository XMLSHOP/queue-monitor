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
            $this->get('/jobs', '\xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController')
                ->name('monitor::jobs');

            /** @var Router $this */
            $this->get('/commands', '\xmlshop\QueueMonitor\Controllers\ShowCommandMonitorController')
                ->name('monitor::commands');

            /** @var Router $this */
            $this->get('/schedulers', '\xmlshop\QueueMonitor\Controllers\ShowSchedulerMonitorController')
                ->name('monitor::schedulers');

            /** @var Router $this */
            $this->get('queue-sizes', '\xmlshop\QueueMonitor\Controllers\QueueSizesChartsController')
                ->name('monitor::queue-sizes');

            /** @var Router $this */
            $this->get('running-now', '\xmlshop\QueueMonitor\Controllers\RunningNowController')
                ->name('monitor::running-now');

            if (config('monitor.ui.allow_deletion')) {
                /** @var Router $this */
                $this->delete('monitors/{monitor}', '\xmlshop\QueueMonitor\Controllers\DeleteMonitorController')
                    ->name('monitor::destroy');
            }

            if (config('monitor.ui.allow_purge')) {
                /** @var Router $this */
                $this->delete('purge', '\xmlshop\QueueMonitor\Controllers\PurgeMonitorsController')
                    ->name('monitor::purge');
            }
        };
    }
}
