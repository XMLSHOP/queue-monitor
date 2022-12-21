<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\System;

use Carbon\Carbon;

interface SystemResourceInterface
{
    public function getMemoryUseMb(): float;

    public function getCpuUse(): float;

    public function getTimeElapsed(Carbon $startedAt, Carbon $finishedAt): float;

    public function getProcessId(): int;

    public function getParentProcessId(): int|false;

    public function getHost(): string;

    public function isProcessIdRunning(int $pid): bool;

}
