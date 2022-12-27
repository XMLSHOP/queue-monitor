<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\System;

use Carbon\Carbon;
use Exception;
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
        try {
            $proc = file_get_contents('/proc/' . $this->getParentProcessId() . '/cmdline');
            if (str_contains($proc, 'schedule') && str_contains($proc, 'artisan')) {
                return true;
            }
        } catch (Exception $e) {

        }

        return false;
    }

    public function execCmd($cmd): string
    {
        return trim(shell_exec("$cmd 2>&1"));
    }

    public function getHost(): string
    {
        $host = gethostname();
        return false !== $host ? $host : 'unknown';
    }

    public function isProcessIdRunning(?int $pid): bool
    {
        if (null !== $pid) {
            return false;
        }

        return (bool)file_get_contents('/proc/' . $pid . '/cmdline');
    }

    /**
     * Int might be 5 or 15 (last 5 or 15 minutes)
     * By default - last minute index
     *
     * @param int $last
     * @return float
     */
    public function getLoadAverage(int $last = 1): float
    {
        $loadAvg = sys_getloadavg();

        if (!$loadAvg) {
            return 0;
        }

        return match ($last) {
            15 => $loadAvg[2],
            5 => $loadAvg[1],
            default => $loadAvg[0],
        };
    }
}
