<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Repository\Interfaces\JobRepositoryInterface;

class QueuedJobsDataService
{
    public function __construct(private JobRepositoryInterface $jobRepository)
    {
    }

    public function execute(array $requestData): Collection|array
    {
        return $this->jobRepository->getJobsStatistic(
            $requestData['filter']['date_from'],
            $requestData['filter']['date_to'],
        );
    }
}
