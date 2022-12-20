<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueSizeRepositoryInterface;

class QueueSizesDataService
{
    public function __construct(private QueueSizeRepositoryInterface $queueSizesRepository)
    {
    }

    public function execute(array $requestData): array
    {
        $data = [];

        foreach (config('monitor.dashboard-charts.root') as $chartOptions) {
            $obj = [];
            if (Arr::exists($chartOptions, 'queues')) {
                $obj = array_merge($obj, $chartOptions['properties']);

                $dataToTransform = $this->queueSizesRepository->getDataSegment(
                    $requestData['filter']['date_from'],
                    $requestData['filter']['date_to'],
                    $chartOptions['queues'],
                )->get();

                $obj['data'] = $this->transformData($dataToTransform);
            }

            $data[] = $obj;
        }

        return $data;
    }

    private function transformData(Collection|array $data): array
    {
        $queues = [];
        foreach ($data as $record) {
            $queuesRaw = explode(',', $record->queue_names);
            foreach ($queuesRaw as $item) {
                if ( ! in_array($item, $queues)) {
                    $queues[] = $item;
                }
            }
        }

        if (empty($queues) && 0 == count($data)) {
            return [];
        }

        $out[] = array_merge(['created_at'], $queues);
        foreach ($data as $record) {
            $outElement = [];

            $objQueues = explode(',', $record->queue_names);
            $objSizes = explode(',', $record->sizes);
            foreach (array_merge(['created_at'], $queues) as $queue) {
                if ('created_at' == $queue) {
                    $outElement[] = $record->created_at;
                    continue;
                }

                $index = array_search($queue, $objQueues);
                if (false !== $index) {
                    $outElement[] = (int) $objSizes[$index];
                } else {
                    $outElement[] = null;
                }
            }

            $out[] = $outElement;
        }

        return $out;
    }
}
