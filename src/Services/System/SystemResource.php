<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\System;

use Carbon\Carbon;
use function getrusage;
use function last;
use function memory_get_usage;
use function posix_getpgid;
use function posix_getpid;
use function posix_getppid;
use function round;

class SystemResource implements SystemResourceInterface
{
    public function getMemoryUseMb(): float
    {
        $mem = memory_get_usage(true);

        return round($mem / 1048576, 2);
    }

    public function getCpuUse(): float
    {
        $cpu = getrusage();

        return $cpu['ru_utime.tv_sec'] + $cpu['ru_utime.tv_usec'] / 1000000;
    }

    public function getTimeElapsed(Carbon $startedAt, Carbon $finishedAt): float
    {
        return (float)$startedAt->diffInSeconds($finishedAt) + $startedAt->diff($finishedAt)->f;
    }

    public function getProcessId(): int
    {
        return posix_getpid();
    }

    public function getParentProcessId(): int
    {
        return posix_getpgid(posix_getppid());
    }

    public function isParentProcessScheduler(): bool
    {
        $output = explode("\n", $this->execCmd('ps -f -p ' . $this->getParentProcessId()));
        if (count($output) > 1 && str_contains(last(preg_split('/ +/', $output[1])), 'schedule')) {
            return true;
        }

        return false;
    }

    function execCmd($cmd)
    {
        $output = trim(shell_exec("$cmd 2>&1"));
        if ($output !== "") {
            echo "> " . $cmd . "\n";
            echo $output;
        }
        return $output;
    }

}
