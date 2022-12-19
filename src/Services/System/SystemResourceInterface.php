<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\System;

use Carbon\Carbon;

interface SystemResourceInterface
{
    public function getMemoryUseMb(): float;

    public function getCpuUse(): float;

    public function getTimeElapsed(Carbon $startedAt, Carbon $finishedAt): float;

    public function getPid(): int;

    public function getPPid(): int;

}
