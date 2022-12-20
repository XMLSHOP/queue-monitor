<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface ExceptionRepositoryInterface
{
    public function createFromThrowable(\Throwable $throwable): Model;
}
