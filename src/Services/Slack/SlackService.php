<?php

namespace xmlshop\QueueMonitor\Services\Slack;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SlackService
{
    /** @var array<string,string> */
    private array $config;
    /** @var string[] */
    private array $recipients;
    private ?string $from;
    private ?string $image;
    private AnonymousNotifiable $anonymousNotifiable;

    /**
     * @param array<string,string>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('monitor.laravel-slack');
        $this->anonymousNotifiable = Notification::route('slack', $this->config['slack_webhook_url']);
        $this->from = $this->config['application_name'];
        $this->image = $this->config['application_image'];
        $this->recipients = [$this->config['default_channel']];
    }

    public function send(string|SlackMessage $message): void
    {
        try {
            $slackMessages = $this->getSlackMessageArray($message);

            foreach ($slackMessages as $slackMessage) {
                $this->notify($slackMessage);
            }
        } finally {
            $this->recipients = [$this->config['default_channel']];
        }
    }

    public function to($recipient): self
    {
        if ($recipient instanceof Collection) {
            $recipient = $recipient->all();
        }

        $recipients = is_array($recipient) ? $recipient : func_get_args();

        $this->recipients = array_map(
            static function ($recipient) {
                if (is_object($recipient)) {
                    return $recipient->slack_channel;
                }

                return $recipient;
            }, $recipients
        );

        return $this;
    }

    protected function notify(SlackMessage $slackMessage): void
    {
        $this->anonymousNotifiable->notify(new SimpleSlack($slackMessage));
    }

    protected function getSlackMessageArray(string|SlackMessage $message): array
    {
        if ($message instanceof SlackMessage) {
            return [$message];
        }

        $slackMessageArray = [];
        $slackMessage = (new SlackMessage())->content($message);

        if ($this->from) {
            $slackMessage->from($this->from);
        }

        if ($this->image) {
            $slackMessage->image($this->image);
        }

        foreach ($this->recipients as $recipient) {
            $messageClone = clone $slackMessage;
            $slackMessageArray[] = $messageClone->to($recipient);
        }

        return $slackMessageArray;
    }
}
