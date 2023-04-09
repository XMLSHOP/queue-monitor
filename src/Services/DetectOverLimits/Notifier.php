<?php

namespace xmlshop\QueueMonitor\Services\DetectOverLimits;

use Illuminate\Support\Facades\App;
use Pressutto\LaravelSlack\Slack;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;

class Notifier
{
    /**
     * @var ?Slack
     */
    private ?Slack $slack = null;

    public function __construct(private SystemResourceInterface $systemResource)
    {
    }

    private function getChannel(): Slack
    {
        if (null === $this->slack) {
            $this->slack = app(Slack::class);
        }
        return $this->slack->to(config('monitor.alarm.recipient'));
    }

    public function send(string $message): void
    {
        if (!App::environment('local')) {
            $this->getChannel()->send('*[GMT ' . now()->format('H:i') . ']*' . "\n" . $message);
        }

        if (!$this->systemResource->isParentProcessScheduler()) {
            echo $message;
        }
    }

}