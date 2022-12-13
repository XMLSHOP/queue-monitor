<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface JobRepositoryInterface extends BaseRepositoryInterface
{
    public function firstOrCreate(string $name_with_namespace): int;

    public function getJobsStatistic(string $date_from, string $date_to): Collection;

    public function getJobsAlertInfo(int $period_seconds, int $offset_seconds): Collection;
}
