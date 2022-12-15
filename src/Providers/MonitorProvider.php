<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Events\{CommandFinished,
    CommandStarting,
    ScheduledTaskFailed,
    ScheduledTaskFinished,
    ScheduledTaskSkipped,
    ScheduledTaskStarting};
use Illuminate\Queue\Events\{JobExceptionOccurred, JobFailed, JobProcessed, JobProcessing, JobQueued};
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\{Event, Route};
use Illuminate\Support\ServiceProvider;
use xmlshop\QueueMonitor\Commands\{AggregateQueuesSizesCommand, CleanUpCommand, ListenerCommand};
use xmlshop\QueueMonitor\Repository\HostRepository;
use xmlshop\QueueMonitor\Repository\Interfaces\{
    HostRepositoryInterface,
    JobRepositoryInterface,
    MonitorQueueRepositoryInterface,
    QueueRepositoryInterface,
    QueueSizeRepositoryInterface,
    ExceptionRepositoryInterface
};
use xmlshop\QueueMonitor\Repository\{
    JobRepository, MonitorQueueRepository, QueueRepository, QueueSizeRepository, ExceptionRepository
};
use xmlshop\QueueMonitor\Routes\QueueMonitorRoutes;
use xmlshop\QueueMonitor\Services\{QueueMonitorService, SchedulerMonitorService, CommandMonitorService};

class MonitorProvider extends ServiceProvider
{
    private Dispatcher $dispatcher;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->dispatcher = $app->make(Dispatcher::class);
    }

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

        $this->loadViewsFrom(__DIR__ . '/../../views', 'monitor');

        /** @phpstan-ignore-next-line */
        Route::mixin(new QueueMonitorRoutes());

        if (!config('monitor.settings.active')) {
            return;
        }

        config('monitor.settings.active-monitor-scheduler') && $this->listenSchedullers();
        config('monitor.settings.active-monitor-commands') && $this->listenCommand();
        config('monitor.settings.active-monitor-queue-jobs') && $this->listenQueues();
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
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/db.php', 'monitor.db');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/ui.php', 'monitor.ui');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/alarm.php', 'monitor.alarm');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/queue-sizes-retrieves.php', 'monitor.queue-sizes-retrieves');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/dashboard-charts.php', 'monitor.dashboard-charts');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/settings.php', 'monitor.settings');
        }

        $this->app->bind(MonitorQueueRepositoryInterface::class, MonitorQueueRepository::class);
        $this->app->bind(QueueRepositoryInterface::class, QueueRepository::class);
        $this->app->bind(HostRepositoryInterface::class, HostRepository::class);
        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
        $this->app->bind(QueueSizeRepositoryInterface::class, QueueSizeRepository::class);
        $this->app->bind(ExceptionRepositoryInterface::class, ExceptionRepository::class);

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
        $queueManager = $this->app->make(QueueManager::class);
        $queueMonitorService = $this->app->make(QueueMonitorService::class);

        $this->dispatcher->listen(
            JobQueued::class,
            static fn (JobQueued $event) => $queueMonitorService->handleJobQueued($event)
        );

        $queueManager->before(
            static fn (JobProcessing $event) => $queueMonitorService->handleJobProcessing($event)
        );
        $queueManager->after(
            static fn (JobProcessed $event) => $queueMonitorService->handleJobProcessed($event)
        );
        $queueManager->failing(
            static fn (JobFailed $event) => $queueMonitorService->handleJobFailed($event)
        );
        $queueManager->exceptionOccurred(
            static fn (JobExceptionOccurred $event) => $queueMonitorService->handleJobExceptionOccurred($event)
        );
    }

    private function listenSchedullers(): void
    {
        $schedulerMonitorService = $this->app->make(SchedulerMonitorService::class);

        $this->dispatcher->listen(
            ScheduledTaskStarting::class,
            static fn (ScheduledTaskStarting $event) => $schedulerMonitorService->handleTaskStarting($event)
        );

        $this->dispatcher->listen(
            ScheduledTaskFinished::class,
            static fn (ScheduledTaskFinished $event) => $schedulerMonitorService->handleTaskFinished($event)
        );

        $this->dispatcher->listen(
            ScheduledTaskFailed::class,
            static fn (ScheduledTaskFailed $event) => $schedulerMonitorService->handleTaskFailed($event)
        );

        $this->dispatcher->listen(
            ScheduledTaskSkipped::class,
            static fn (ScheduledTaskSkipped $event) => $schedulerMonitorService->handleTaskSkipped($event)
        );
    }

    private function listenCommand(): void
    {
        $commandMonitorService = $this->app->make(CommandMonitorService::class);

        $this->dispatcher->listen(
            CommandStarting::class,
            static fn (CommandStarting $event) => $commandMonitorService->handleCommandStarting($event),
        );

        $this->dispatcher->listen(
            CommandFinished::class,
            static fn (CommandFinished $event) => $commandMonitorService->handleCommandFinished($event),
        );
    }
}
