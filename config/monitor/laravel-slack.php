<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bot User OAuth Token
    |--------------------------------------------------------------------------
    |
    | The OAuth token for your Slack bot user. This is used to authenticate
    | API requests made on behalf of your bot. You can find or generate this
    | token in your Slack app's OAuth & Permissions settings page.
    |
    */

    'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Channel
    |--------------------------------------------------------------------------
    |
    | If no recipient is specified the message will delivered to this channel.
    | You can set a default user by using '@' instead of '#'
    |
    */

    'default_channel' => '#general',

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The username that this integration will post as.
    | Leave null to use Slack's default.
    |
    */

    'application_name' => env('APP_NAME', null),

    /*
    |--------------------------------------------------------------------------
    | Application Image
    |--------------------------------------------------------------------------
    |
    | The user image that is used for messages from this integration.
    | Leave null to use Slack's default.
    | It should be a valid URL.
    |
    */

    'application_image' => null,

];
