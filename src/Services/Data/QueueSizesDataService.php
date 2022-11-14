<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueSizesRepository;

class QueueSizesDataService
{
    /**
     * @param array $requestData
     * @return array
     */
    public function execute(array $requestData): array
    {
        /** @var QueueMonitorQueueSizesRepository $queuesSizeRepository */
        $queuesSizeRepository = app(QueueMonitorQueueSizesRepository::class);
        $data = [];

        foreach (config('queue-monitor.dashboard-charts') as $chartOptions) {
            $obj = [];
            if (Arr::exists($chartOptions, 'queues')) {
                $obj = array_merge($obj, $chartOptions['properties']);
                $obj['data'] =
                    $this->transformData(
                        $queuesSizeRepository->getDataSegment(
                            $requestData['filter']['date_from'],
                            $requestData['filter']['date_to'],
                            $chartOptions['queues']
                        )->get()
                    );
            }

            $data[] = $obj;
        }
        return $data;
    }

    /**
     * @param Collection|array $data
     * @return array
     */
    private function transformData(Collection|array $data): array
    {
        $queues = [];
        foreach ($data as $record) {
            $queuesRaw = explode(',', $record->queue_names);
            foreach ($queuesRaw as $item) {
                if (!in_array($item, $queues)) {
                    $queues[] = $item;
                }
            }
        }

        if (empty($queues) && count($data) == 0) {
            return [];
        }

        $out[] = array_merge(['created_at'], $queues);
        foreach ($data as $record) {
            $outElement = [];

            $objQueues = explode(',', $record->queue_names);
            $objSizes = explode(',', $record->sizes);
            foreach (array_merge(['created_at'], $queues) as $queue) {
                if ($queue == 'created_at') {
                    $outElement[] = $record->created_at;
                    continue;
                }

                $index = array_search($queue, $objQueues);
                if ($index !== false) {
                    $outElement[] = (int)$objSizes[$index];
                } else {
                    $outElement[] = null;
                }
            }

            $out[] = $outElement;

        }
        return $out;
    }

}