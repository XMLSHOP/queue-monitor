<?php

namespace xmlshop\QueueMonitor\Services\DetectOverLimits;

use Illuminate\Support\Facades\App;
use xmlshop\QueueMonitor\Services\Slack\Slack;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class Notifier
{
    public function __construct(private SystemResourceInterface $systemResource)
    {
    }

    public function send(string $message): void
    {
        if (!App::environment('local')) {
            Slack::to(config('monitor.alarm.recipient'))
                ->send('*[GMT ' . now()->format('H:i') . ']*' . "\n" . $message);
        }

        if (!$this->systemResource->isParentProcessScheduler()) {
            echo $message;
        }
    }

}
