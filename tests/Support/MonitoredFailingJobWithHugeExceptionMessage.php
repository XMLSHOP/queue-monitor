<?php

namespace xmlshop\QueueMonitor\Tests\Support;

use xmlshop\QueueMonitor\Traits\IsMonitored;

class MonitoredFailingJobWithHugeExceptionMessage extends BaseJob
{
    use IsMonitored;

    public function handle(): void
    {
        throw new IntentionallyFailedException(
            str_repeat('x', config('monitor.db.max_length_exception_message') + 10)
        );
    }
}
