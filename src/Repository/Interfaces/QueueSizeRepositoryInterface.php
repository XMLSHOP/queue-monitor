<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface QueueSizeRepositoryInterface extends BaseRepositoryInterface
{
    public function bulkInsert(array $data): bool;

    public function getDataSegment(string $from, string $to, ?array $queues = null): Builder;

    public function purge(int $days): void;
}
