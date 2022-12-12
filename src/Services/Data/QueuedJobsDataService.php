<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;

class QueuedJobsDataService
{
    public function __construct(private QueueMonitorJobsRepository $queueMonitorJobsRepository)
    {
    }

    public function execute(array $requestData): Collection|array
    {
        return $this->queueMonitorJobsRepository->getJobsStatistic(
            $requestData['filter']['date_from'],
            $requestData['filter']['date_to'],
        );
    }
}
