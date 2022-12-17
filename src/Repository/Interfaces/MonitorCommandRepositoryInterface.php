<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use xmlshop\QueueMonitor\Models\Command;
use xmlshop\QueueMonitor\Models\Host;

interface MonitorCommandRepositoryInterface
{
    public function createByCommandAndHost(Command $command, Host $host): void;

    public function updateByCommandAndHost(Command $command, Host $host): void;
}