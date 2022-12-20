<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use xmlshop\QueueMonitor\Services\System\{SystemResource, SystemResourceInterface};
use Illuminate\Console\Events\{
    CommandFinished,
    CommandStarting,
    ScheduledTaskFailed,
    ScheduledTaskFinished,
    ScheduledTaskStarting};
use Illuminate\Support\Collection;
use Illuminate\Queue\Events\{JobExceptionOccurred, JobFailed, JobProcessed, JobProcessing, JobQueued};
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use xmlshop\QueueMonitor\Commands\{AggregateQueuesSizesCommand, CleanUpCommand, ListenerCommand, SyncCommand};
use xmlshop\QueueMonitor\Repository\HostRepository;
use xmlshop\QueueMonitor\Repository\Interfaces\{
    CommandRepositoryInterface,
    HostRepositoryInterface,
    JobRepositoryInterface,
    MonitorCommandRepositoryInterface,
    MonitorQueueRepositoryInterface,
    MonitorSchedulerRepositoryInterface,
    QueueRepositoryInterface,
    QueueSizeRepositoryInterface,
    ExceptionRepositoryInterface,
    SchedulerRepositoryInterface};
use xmlshop\QueueMonitor\Repository\{
    CommandRepository,
    JobRepository,
    MonitorCommandRepository,
    MonitorQueueRepository,
    MonitorSchedulerRepository,
    QueueRepository,
    QueueSizeRepository,
    ExceptionRepository,
    SchedulerRepository};
use xmlshop\QueueMonitor\Routes\QueueMonitorRoutes;
use xmlshop\QueueMonitor\Services\{QueueMonitorService, SchedulerMonitorService, CommandMonitorService};

class MonitorProvider extends ServiceProvider
{
    private Dispatcher $dispatcher;

    private Collection $resourcesToBind;

    private array $containerResourcesMap = [
        SystemResourceInterface::class => SystemResource::class,
        MonitorQueueRepositoryInterface::class => MonitorQueueRepository::class,
        QueueRepositoryInterface::class => QueueRepository::class,
        HostRepositoryInterface::class => HostRepository::class,
        JobRepositoryInterface::class => JobRepository::class,
        QueueSizeRepositoryInterface::class => QueueSizeRepository::class,
        ExceptionRepositoryInterface::class => ExceptionRepository::class,
        SchedulerRepositoryInterface::class => SchedulerRepository::class,
        MonitorSchedulerRepositoryInterface::class => MonitorSchedulerRepository::class,
        CommandRepositoryInterface::class => CommandRepository::class,
        MonitorCommandRepositoryInterface::class => MonitorCommandRepository::class,
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->dispatcher = $app->make(Dispatcher::class);
        $this->resourcesToBind = new Collection($this->containerResourcesMap);
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

        config('monitor.settings.active-monitor-scheduler') && $this->listenSchedulers();
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
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/dashboard-charts.php', 'monitor.dashboard-charts');
            $this->mergeConfigFrom(__DIR__ . '/../../config/monitor/settings.php', 'monitor.settings');
        }

        $this->resourcesToBind->each(fn ($concrete, $abstract) => $this->app->bind($abstract, $concrete));

        /** @phpstan-ignore-next-line */
        $this->app->bind('monitor:aggregate-queues-sizes', AggregateQueuesSizesCommand::class);
        $this->app->bind('monitor:clean-up', CleanUpCommand::class);
        $this->app->bind('monitor:listener', ListenerCommand::class);
        $this->app->bind('monitor:sync-scheduler', SyncCommand::class);

        $this->commands([
            'monitor:aggregate-queues-sizes',
            'monitor:clean-up',
            'monitor:listener',
            'monitor:sync-scheduler'
        ]);
    }

    private function listenQueues(): void
    {
        /** @var QueueManager $queueManager */
        $queueManager = $this->app->make(QueueManager::class);
        /** @var QueueMonitorService $queueMonitorService */
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

    private function listenSchedulers(): void
    {
        /** @var SchedulerMonitorService $schedulerMonitorService */
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
    }

    private function listenCommand(): void
    {
        /** @var CommandMonitorService $commandMonitorService */
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
