<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;

class RunningNowController
{
    public function __invoke(Request $request, HostRepositoryInterface $hostRepository): JsonResponse|View
    {
        $runningNow = $hostRepository->getRunningNowInfo()->toArray();

        if ($request->wantsJson() && $request->ajax()) {
            return new JsonResponse(['data' => ['hosts' => $runningNow]]);
        }

        return view('monitor::running-now.index', ['data' => ['hosts' => $runningNow]]);
    }
}
