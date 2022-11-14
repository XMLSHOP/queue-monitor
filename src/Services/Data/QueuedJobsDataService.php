<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Database\Eloquent\Collection;
use xmlshop\QueueMonitor\Repository\QueueMonitorJobsRepository;

class QueuedJobsDataService
{
    /**
     * @param array $requestData
     * @return Collection|array
     */
    public function execute(array $requestData): Collection|array
    {
        /** @var QueueMonitorJobsRepository $queuedJobsRepository */
        $queuedJobsRepository = app(QueueMonitorJobsRepository::class);
        return $queuedJobsRepository->getJobsStatistic(
            $requestData['filter']['date_from'],
            $requestData['filter']['date_to']
        );
    }
}
