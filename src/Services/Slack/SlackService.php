<?php

namespace xmlshop\QueueMonitor\Services\Slack;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SlackService
{
    /** @var array<string,string> */
    private array $config;
    /** @var AnonymousNotifiable[] */
    private array $notifiables;
    private ?string $image;
    private ?string $oauthKey;
    private string $applicationName;

    /**
     * @param array<string,string>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('monitor.laravel-slack');
        $this->oauthKey = $this->config['bot_user_oauth_token'];
        $this->image = $this->config['application_image'];
        $this->applicationName = $this->config['application_name'] ?: '';
        $this->notifiables = [$this->buildNotifiable($this->config['default_channel'])];
    }

    public function send(string|SlackMessage|array $message): void
    {
        try {
            Notification::send($this->notifiables, new SimpleSlack($this->buildSlackMessage($message)));
        } finally {
            $this->notifiables = [$this->buildNotifiable($this->config['default_channel'])];
        }
    }

    public function to($recipient): self
    {
        if ($recipient instanceof Collection) {
            $recipient = $recipient->all();
        }

        $recipients = is_array($recipient) ? $recipient : func_get_args();

        $this->notifiables = array_map(
            function ($recipient) {
                if (is_object($recipient)) { // todo: questionable. how do I know this property?
                    $recipient = $recipient->slack_channel;
                }

                return $this->buildNotifiable($recipient);
            }, $recipients
        );

        return $this;
    }

    protected function buildNotifiable(string $recipient): AnonymousNotifiable
    {
        return Notification::route('slack', new SlackRoute($recipient, $this->oauthKey));
    }

    protected function buildSlackMessage(string|SlackMessage|array $message): SlackMessage
    {
        if ($message instanceof SlackMessage) {
            return $message;
        }

        $slackMessage = (new SlackMessage())
            ->headerBlock('Queue Monitor | ' . $this->applicationName)
            ->text(is_array($message) ? implode("\n", $message) : $message)
            ->contextBlock(fn (ContextBlock $block) => $block->text(now()->toCookieString()));

        if ($this->image) {
            $slackMessage->imageBlock($this->image, 'Queue Monitor | IMAGE');
        }

        if (is_array($message)) {
            while ($msg = array_shift($message)) {
                $slackMessage->sectionBlock(fn (SectionBlock $block) => $block->text($msg)->markdown());
                $slackMessage->dividerBlock();
            }
        } else {
            $slackMessage->sectionBlock(fn (SectionBlock $block) => $block->text($message)->markdown());
        }

        return $slackMessage;
    }
}
