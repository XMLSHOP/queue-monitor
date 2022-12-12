<?php

namespace xmlshop\QueueMonitor\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
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
use xmlshop\QueueMonitor\EventHandlers\CommandListener;
use xmlshop\QueueMonitor\EventHandlers\ScheduledTaskEventSubscriber;
use xmlshop\QueueMonitor\Repository\Contracts\QueueMonitorRepositoryContract;
use xmlshop\QueueMonitor\Repository\QueueMonitorRepository;
use xmlshop\QueueMonitor\Routes\QueueMonitorRoutes;
use xmlshop\QueueMonitor\Services\QueueMonitorService;

class MonitorProvider extends ServiceProvider
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
                $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
            }

            $this->publishes([
                __DIR__ . '/../../config/monitor/db.php' => config_path('monitor/db.php'),
                __DIR__ . '/../../config/monitor/ui.php' => config_path('monitor/ui.php'),
                __DIR__ . '/../../config/monitor/alarm.php' => config_path('monitor/alarm.php'),
                __DIR__ . '/../../config/monitor/queue-sizes-retrieves.php' => config_path('monitor/queue-sizes-retrieves.php'),
                __DIR__ . '/../../config/monitor/dashboard-charts.php' => config_path('monitor/dashboard-charts.php'),
                __DIR__ . '/../../config/monitor/settings.php' => config_path('monitor/settings.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../migrations' => database_path('migrations'),
            ], 'migrations');

            $this->publishes([
                __DIR__ . '/../../views' => resource_path('views/vendor/monitor'),
            ], 'views');
        }

        $this->loadViewsFrom(
            __DIR__ . '/../../views',
            'monitor'
        );

        /** @phpstan-ignore-next-line */
        Route::mixin(new QueueMonitorRoutes());

        if (!config('monitor.settings.active')) {
            return;
        }

        if (config('monitor.settings.active-monitor-scheduler')) {
//            Event::subscribe(ScheduledTaskEventSubscriber::class);
        }

        if (config('monitor.settings.active-monitor-commands')) {
//            Event::listen(CommandStarting::class, CommandListener::class);
//            Event::listen(CommandFinished::class, CommandListener::class);
        }

        if (config('monitor.settings.active-monitor-queue-jobs')) {
            $this->listenQueues();
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        /** @phpstan-ignore-next-line */
        if (!$this->app->configurationIsCached()) {
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

            $this->mergeConfigFrom(
                __DIR__ . '/../../config/monitor/settings.php',
                'monitor.settings');
        }

        $this->app->bind(QueueMonitorRepositoryContract::class, QueueMonitorRepository::class);

        /** @phpstan-ignore-next-line */
        $this->app->bind('monitor:aggregate-queues-sizes', AggregateQueuesSizesCommand::class);
        $this->app->bind('monitor:clean-up', CleanUpCommand::class);
        $this->app->bind('monitor:listener', ListenerCommand::class);

        $this->commands([
            'monitor:aggregate-queues-sizes',
            'monitor:clean-up',
            'monitor:listener',
        ]);
    }

    private function listenQueues(): void
    {
        /** @var QueueManager $manager */
        $manager = app(QueueManager::class);
        /** @var QueueMonitorService $queueMonitorService */
        $queueMonitorService = app(QueueMonitorService::class);

        Event::listen([JobQueued::class], function (JobQueued $jobQueued) use ($queueMonitorService) {
            $queueMonitorService->handleJobQueued($jobQueued);
        });

        $manager->before(static function (JobProcessing $jobProcessing) use ($queueMonitorService) {
            $queueMonitorService->handleJobProcessing($jobProcessing);
        });

        $manager->after(static function (JobProcessed $jobProcessed) use ($queueMonitorService) {
            $queueMonitorService->handleJobProcessed($jobProcessed);
        });

        $manager->failing(static function (JobFailed $jobFailed) use ($queueMonitorService) {
            $queueMonitorService->handleJobFailed($jobFailed);
        });

        $manager->exceptionOccurred(static function (JobExceptionOccurred $jobException) use ($queueMonitorService) {
            $queueMonitorService->handleJobExceptionOccurred($jobException);
        });
    }
}
