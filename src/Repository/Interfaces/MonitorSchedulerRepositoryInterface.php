<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Support\Collection;
use Throwable;
use xmlshop\QueueMonitor\Models\Exception;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\Scheduler;

interface MonitorSchedulerRepositoryInterface
{
    public function createWithSchedulerAndHost(Scheduler $scheduler, Host $host): void;

    public function updateBySchedulerAndHost(Scheduler $scheduler, Host $host): void;

    public function updateFailedBySchedulerAndHost(Scheduler $scheduler, Host $host, Exception $exceptionModel): void;

    public function foundByParentProcessId(): bool;

    public function getListRunning(): Collection;

    public function updateFailedExternally(string $scheduler_name, Throwable $exception): void;
}
