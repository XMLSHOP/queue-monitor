<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use xmlshop\QueueMonitor\Services\Data\QueuedJobsDataService;
use xmlshop\QueueMonitor\Services\Data\QueueSizesDataService;

class QueueSizesChartsController
{
    private string $dtPattern = '~^\d{4}-\d{2}-\d{2}[\w|\W]\d{2}:\d{2}:\d{2}(\W)~';

    public function __construct(private QueueSizesDataService $queueSizesDataExportService,)
    {
    }

    public function __invoke(Request $request): View|array
    {
        $requestData = $request->validate([
            'filter' => ['nullable', 'array'],
        ]);

        $requestData = $this->getSanitized($requestData);

        $data = [
            'charts' => $this->queueSizesDataExportService->execute($requestData),
        ];

        if ($request->ajax() && $request->wantsJson()) {
            return compact('data');
        }

        return view('monitor::queue-sizes.index', compact('data'));
    }

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
