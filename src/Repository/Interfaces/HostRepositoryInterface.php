<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Models\Host;

interface HostRepositoryInterface extends BaseRepositoryInterface
{
    public function firstOrCreate(): Model|Host;

    public function getRunningNowInfo(): Collection;
}
