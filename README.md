# Queue Monitor
## For Laravel framework

[![Latest Stable Version](https://img.shields.io/packagist/v/xmlshop/queue-monitor.svg?style=flat-square)](https://packagist.org/packages/xmlshop/queue-monitor)
[![Total Downloads](https://img.shields.io/packagist/dt/xmlshop/queue-monitor.svg?style=flat-square)](https://packagist.org/packages/xmlshop/queue-monitor)
[![License](https://img.shields.io/packagist/l/xmlshop/queue-monitor.svg?style=flat-square)](https://packagist.org/packages/xmlshop/queue-monitor)

This package offers monitoring like [Laravel Horizon](https://laravel.com/docs/horizon) for database queue.

This package initially forked from [Laravel-Queue-Monitor](https://github.com/romanzipp/Laravel-Queue-Monitor).

## Features

- Monitor jobs like [Laravel Horizon](https://laravel.com/docs/horizon) for any queue
- Handle failing jobs with storing exception
- Monitor job progress
- Get an estimated time remaining for a job
- Store additional data for a job monitoring

## Installation

```
composer require xmlshop/queue-monitor
```

## Configuration

Copy configuration & migration to your project:

```
php artisan vendor:publish --provider="xmlshop\QueueMonitor\Providers\MonitorProvider"  --tag=config --tag=migrations
```

Migrate the Queue Monitoring table. The table name can be configured in the config file or via the published migration.

```
php artisan migrate
```

## Scheduler
```
class Kernel extends ConsoleKernel
{
    #...
    protected function schedule(Schedule $schedule)
    {
        #...
        $schedule->command('queue-monitor:aggregate-queues-sizes')->everyMinute();
        $schedule->command('queue-monitor:clean-up')->dailyAt('01:23');
        $schedule->command('queue-monitor:listener')->everyMinute();
        $schedule->command('monitor:sync-scheduler')->daily();
        $schedule->command('monitor:pid-checker')->everyMinute(); #for each node, if >1
        #...
    }
}
```

After the listener automatically will be launched `queue-monitor:listener`. It might be disabled in configuration or by command
```bash
#php artisan queue-monitor:listener disable {hours}
php artisan queue-monitor:listener disable 24 #disables alert-launcher for a day. By default 1 hour
php artisan queue-monitor:listener enable #enables that back
```

Application Error handler have to be replaced at your application
Please add custom error handler:
```php

final class Handler extends \Illuminate\Foundation\Exceptions\Handler implements \Illuminate\Contracts\Debug\ExceptionHandler
{
    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Throwable $e
     * @return void
     */
    final public function renderForConsole($output, Throwable $e)
    {
        $command_name = null;
        foreach (request()->server('argv') as $arg) {
            if (Str::contains($arg, ':')) {
                $command_name = $arg;
                break;
            }
        }

        if (null !== $command_name && class_exists('xmlshop\QueueMonitor\Services\CLIFailureHandler')) {
            app('xmlshop\QueueMonitor\Services\CLIFailureHandler')->handle($command_name, $e);
        }

        parent::renderForConsole($output, $e);
    }
}
```
And please register that in ApplicationProvider
```php
$this->app->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, \App\Exceptions\Handler::class);
```


## Alert function
1. Listener looks into database in the `x_queue_monitoring_queue_sizes` table and comparing current amount with amount mentioned in field `alert_threshold`. If exceed - alert.
2. Listener looks into database in the `x_queue_monitoring` table and comparing several metrics (pending time, execution time, etc.)
3. You can manage exceptions for each Job. Including ignore alert.

## Usage

To monitor a job, simply add the `xmlshop\QueueMonitor\Traits\IsMonitored` Trait.

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use xmlshop\QueueMonitor\Traits\IsMonitored; // <---

class ExampleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use IsMonitored; // <---
}
```

**Important!** You need to implement the `Illuminate\Contracts\Queue\ShouldQueue` interface to your job class. Otherwise, Laravel framework will not dispatch any events containing status information for monitoring the job.

## UI

You can enable the optional UI routes by calling `Route::queueMonitor()` inside your route file, similar to the official [ui scaffolding](https://github.com/laravel/ui).

```php
Route::prefix('monitor')->group(function () {
    Route::queueMonitor();
});
```

### Routes

| Route          | Action              |
|----------------| ------------------- |
| `/monitor`     | Show the jobs table |

See the [configuration files](https://github.com/XMLSHOP/queue-monitor/tree/master/config/monitor) for more information.

![Preview](https://raw.githubusercontent.com/xmlshop/queue-monitor/master/preview.png)


## Extended usage

### Progress

You can set a **progress value** (0-100) to get an estimation of a job progression.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class ExampleJob implements ShouldQueue
{
    use IsMonitored;

    public function handle()
    {
        $this->queueProgress(0);

        // Do something...

        $this->queueProgress(50);

        // Do something...

        $this->queueProgress(100);
    }
}
``` 

### Chunk progress

A common scenario for a job is iterating through large collections.

This example job loops through a large amount of users and updates it's progress value with each chunk iteration.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class ChunkJob implements ShouldQueue
{
    use IsMonitored;

    public function handle()
    {
        $usersCount = User::count();

        $perChunk = 50;

        User::query()
            ->chunk($perChunk, function (Collection $users) use ($perChunk, $usersCount) {

                $this->queueProgressChunk($usersCount‚ $perChunk);

                foreach ($users as $user) {
                    // ...
                }
            });
    }
}
```

### Progress cooldown

To avoid flooding the database with rapidly repeating update queries, you can set override the `progressCooldown` method and specify a length in seconds to wait before each progress update is written to the database. Notice that cooldown will always be ignore for the values 0, 25, 50, 75 and 100.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class LazyJob implements ShouldQueue
{
    use IsMonitored;

    public function progressCooldown(): int
    {
        return 10; // Wait 10 seconds between each progress update
    }
}
``` 

### Custom data

This package also allows setting custom data in array syntax on the monitoring model.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class CustomDataJob implements ShouldQueue
{
    use IsMonitored;

    public function handle()
    {
        $this->queueData(['foo' => 'Bar']);
        
        // WARNING! This is overriding the monitoring data
        $this->queueData(['bar' => 'Foo']);

        // To preserve previous data and merge the given payload, set the $merge parameter true
        $this->queueData(['bar' => 'Foo'], true);
    }
}
``` 

In order to show custom data on UI you need to add this line under `config/monitor.php`
```php
'ui' => [
    ...

    'show_custom_data' => true,

    ...
]
```

### Only keep failed jobs

You can override the `keepMonitorOnSuccess()` method to only store failed monitor entries of an executed job. This can be used if you only want to keep  failed monitors for jobs that are frequently executed but worth to monitor. Alternatively you can use Laravel's built in `failed_jobs` table.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class FrequentSucceedingJob implements ShouldQueue
{
    use IsMonitored;

    public static function keepMonitorOnSuccess(): bool
    {
        return false;
    }
}
``` 

### Retrieve processed Jobs

```php
use xmlshop\QueueMonitor\Models\MonitorQueue;

$job = MonitorQueue::query()->first();

// Check the current state of a job
$job->isFinished();
$job->hasFailed();
$job->hasSucceeded();

// If the job is still running, get the estimated seconds remaining
// Notice: This requires a progress to be set
$job->getRemainingSeconds();
$job->getRemainingInterval(); // Carbon\CarbonInterval

// Retrieve any data that has been set while execution
$job->getData();

// Get the base name of the executed job
$job->getBasename();
```

### Model Scopes

```php
use xmlshop\QueueMonitor\Models\MonitorQueue;

// Filter by Status
MonitorQueue::failed();
MonitorQueue::succeeded();

// Filter by Date
MonitorQueue::lastHour();
MonitorQueue::today();

// Chain Scopes
MonitorQueue::today()->failed();
```
