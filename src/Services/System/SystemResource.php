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

    public function getParentProcessId(): int|false
    {
        return posix_getpgid(posix_getppid());
    }

    public function isParentProcessScheduler(): bool
    {
        if(!$this->getParentProcessId()) {
            return false;
        }

        $output = explode("\n", $this->execCmd('ps -f -p ' . $this->getParentProcessId()));
        if (count($output) > 1 && str_contains(last(preg_split('/ +/', $output[1])), 'schedule')) {
            return true;
        }

        return false;
    }

    function execCmd($cmd): string
    {
        return trim(shell_exec("$cmd 2>&1"));
    }

    public function getHost(): string
    {
        $host = gethostname();
        return false !== $host ? $host : 'unknown';
    }

    public function isProcessIdRunning(int $pid): bool
    {
        return count(explode("\n", $this->execCmd('ps -f -p ' . $pid))) > 1;
    }
}
