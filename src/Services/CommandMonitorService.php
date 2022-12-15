<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class CommandMonitorService
{
    public function handleCommandStarting(CommandStarting $event): void
    {

    }

    public function handleCommandFinished(CommandFinished $event): void
    {

    }
}
