<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

interface HostRepositoryInterface extends BaseRepositoryInterface
{
    public function firstOrCreate():int;
}
