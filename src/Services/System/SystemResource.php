<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\System;

use Carbon\Carbon;

class SystemResource
{
    public function getMemoryUseMb(): float
    {
        $mem = \memory_get_usage(true);

        return \round($mem / 1048576, 2);
    }

    public function getCpuUse(): float
    {
        $cpu = \getrusage();

        return $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;
    }

    public function getTimeElapsed(Carbon $startedAt, Carbon $finishedAt): float
    {
        return (float) $startedAt->diffInSeconds($finishedAt) + $startedAt->diff($finishedAt)->f;
    }
}
