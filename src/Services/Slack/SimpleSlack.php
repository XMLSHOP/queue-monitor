<?php

namespace xmlshop\QueueMonitor\Services\Slack;

use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SimpleSlack extends Notification
{
    public function __construct(
        protected SlackMessage $message,
    ) {
    }

    public function via($notifiable): array
    {
        return ['slack'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return $this->message;
    }
}
