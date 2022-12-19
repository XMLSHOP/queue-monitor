<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Command;

interface CommandRepositoryInterface
{
    public function firstOrCreateByEvent(CommandFinished|CommandStarting $event): Command|Model;

    public function getList(string $string);
}
