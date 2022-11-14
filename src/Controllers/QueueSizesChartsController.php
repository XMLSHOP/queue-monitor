<?php
declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Services\Data\QueuedJobsDataService;
use xmlshop\QueueMonitor\Services\Data\QueueSizesDataService;


class QueueSizesChartsController
{
    private string $dtPattern = '~^\d{4}-\d{2}-\d{2}[\w|\W]\d{2}:\d{2}:\d{2}(\W)~';

    /**
     * @param Request $request
     * @return array[]|Application|Factory|View
     */
    public function __invoke(Request $request)
    {
        $requestData = $request->validate([
            'filter' => ['nullable', 'array'],
        ]);
        $requestData = $this->getSanitized($requestData);

        $queueSizesDataExportService = app(QueueSizesDataService::class);
        $queuedJobsDataService = app(QueuedJobsDataService::class);

        $data = [
            'charts' => $queueSizesDataExportService->execute($requestData),
            'jobs' => $queuedJobsDataService->execute($requestData),
        ];

//        $request->attributes->add(['filter' => $requestData['filter'],]);
//        $request->request->add(['filter' => $requestData['filter'],]);
//        $request->merge(['filter' => $requestData['filter'],]);

        if ($request->ajax()) {
            return ['data' => $data];
        }

        return view('queue-monitor::queue-sizes/index', [
            'data' => $data
        ]);
    }

    /**
     * @param array $sanitized
     * @return array
     */
    private function getSanitized(array $sanitized): array
    {
        if (!Arr::exists($sanitized, 'filter') || null === $sanitized['filter']) {
            $sanitized['filter'] = [];
        }

        if (Arr::exists($sanitized['filter'], 'date_from') && preg_match($this->dtPattern, $sanitized['filter']['date_from'], $matches)) {
            $sanitized['filter']['date_from'] = Carbon::createFromTimeString(
                explode($matches[1], $sanitized['filter']['date_from'])[0]
            )->toDateTimeString();
            unset($matches);
        } else {
            $sanitized['filter']['date_from'] = Carbon::now('Europe/London')->addHours(-3)->toDateTimeString();
        }

        if (Arr::exists($sanitized['filter'], 'date_to') && preg_match($this->dtPattern, $sanitized['filter']['date_to'], $matches)) {
            $sanitized['filter']['date_to'] = Carbon::createFromTimeString(
                explode($matches[1], $sanitized['filter']['date_to'])[0]
            )->toDateTimeString();
            unset($matches);
        } else {
            $sanitized['filter']['date_to'] = Carbon::now('Europe/London')->toDateTimeString();
        }

        return $sanitized;
    }
}