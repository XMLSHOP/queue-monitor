<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers;

use Illuminate\Http\JsonResponse;

class RunningNowController
{
    public function __invoke(): JsonResponse
    {

        return new JsonResponse([

        ]);
    }
}
