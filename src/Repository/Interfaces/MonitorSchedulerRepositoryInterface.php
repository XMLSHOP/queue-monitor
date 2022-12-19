<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use xmlshop\QueueMonitor\Models\Exception;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\Scheduler;

interface MonitorSchedulerRepositoryInterface
{
    public function createWithSchedulerAndHost(Scheduler $scheduler, Host $host): void;

    public function updateBySchedulerAndHost(Scheduler $scheduler, Host $host): void;

    public function updateFailedBySchedulerAndHost(Scheduler $scheduler, Host $host, Exception $exceptionModel): void;

    public function foundByPPid(): bool;
}
