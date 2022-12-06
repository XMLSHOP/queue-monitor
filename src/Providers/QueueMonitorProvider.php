<?php

namespace xmlshop\QueueMonitor\Providers;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use xmlshop\QueueMonitor\Commands\AggregateQueuesSizesCommand;
use xmlshop\QueueMonitor\Commands\CleanUpCommand;
use xmlshop\QueueMonitor\Commands\ListenerCommand;
use xmlshop\QueueMonitor\Routes\QueueMonitorRoutes;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class QueueMonitorProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->app->runningInConsole()) {
            if (QueueMonitorService::$loadMigrations) {
                $this->loadMigrationsFrom(
                    __DIR__ . '/../../migrations'
                );
            }

            $this->publishes([
                __DIR__ . '/../../config/monitor/db.php' => config_path('monitor/db.php'),
            ], 'config');
            $this->publishes([
                __DIR__ . '/../../config/monitor/ui.php' => config_path('monitor/ui.php'),
            ], 'config');
            $this->publishes([
                __DIR__ . '/../../config/monitor/alarm.php' => config_path('monitor/alarm.php'),
            ], 'config');
            $this->publishes([
                __DIR__ . '/../../config/monitor/queue-sizes-retrieves.php' => config_path('monitor/queue-sizes-retrieves.php'),
            ], 'config');
            $this->publishes([
                __DIR__ . '/../../config/monitor/dashboard-charts.php' => config_path('monitor/dashboard-charts.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../migrations' => database_path('migrations'),
            ], 'migrations');

            $this->publishes([
                __DIR__ . '/../../views' => resource_path('views/vendor/queue-monitor'),
            ], 'views');
        }

        $this->loadViewsFrom(
            __DIR__ . '/../../views',
            'queue-monitor'
        );

        /** @phpstan-ignore-next-line */
        Route::mixin(new QueueMonitorRoutes());

        if ( ! config('monitor.settings.active')) {
            return;
        }

        Event::listen([
            JobQueued::class,
        ], function (JobQueued $event) {
            // Event happens when we do Job::dispatch(...)
            QueueMonitorService::handleJobQueued($event);
        });

        /** @var QueueManager $manager */
        $manager = app(QueueManager::class);

        $manager->before(static function (JobProcessing $event) {
            QueueMonitorService::handleJobProcessing($event);
        });

        $manager->after(static function (JobProcessed $event) {
            QueueMonitorService::handleJobProcessed($event);
        });

        $manager->failing(static function (JobFailed $event) {
            QueueMonitorService::handleJobFailed($event);
        });

        $manager->exceptionOccurred(static function (JobExceptionOccurred $event) {
            QueueMonitorService::handleJobExceptionOccurred($event);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        /** @phpstan-ignore-next-line */
        if ( ! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/db.php',
                'monitor.db'
            );

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/ui.php',
                'monitor.ui'
            );

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/alarm.php',
                'monitor.alarm'
            );

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/queue-sizes-retrieves.php',
                'monitor.queue-sizes-retrieves'
            );

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/dashboard-charts.php',
                'monitor.dashboard-charts'
            );
        }

        /** @phpstan-ignore-next-line */
        $this->app->bind('queue-monitor:aggregate-queues-sizes', AggregateQueuesSizesCommand::class);
        $this->app->bind('queue-monitor:clean-up', CleanUpCommand::class);
        $this->app->bind('queue-monitor:listener', ListenerCommand::class);
        $this->commands([
            'queue-monitor:aggregate-queues-sizes',
            'queue-monitor:clean-up',
            'queue-monitor:listener',
        ]);
    }
}
