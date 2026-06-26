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

    public function send(string|array $message): void
    {
        if (!is_array($message)) {
            $message = [$message];
        }

        if (!App::environment('local')) {
            Slack::to(config('monitor.alarm.recipient'))->send($message);
        }

        if (!$this->systemResource->isParentProcessScheduler()) {
            echo implode("\n", $message);
        }
    }

}
