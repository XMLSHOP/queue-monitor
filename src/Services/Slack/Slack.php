<?php

namespace xmlshop\QueueMonitor\Services\Slack;

use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void         send(string|SlackMessage|array $message)
 * @method static SlackService to($recipient)
 */
class Slack extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SlackService::class;
    }
}
