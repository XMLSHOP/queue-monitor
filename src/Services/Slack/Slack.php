<?php

namespace xmlshop\QueueMonitor\Services\Slack;

use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void         send(string|SlackMessage $message)
 * @method static SlackService to($recipient)
 */
class Slack extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SlackService::class;
    }
}
