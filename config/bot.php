<?php

/**
 * Telegram Bot Configuration
 *
 * Usage: $config = require __DIR__ . '/config/bot.php';
 *        $token  = $config['token'];
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Bot Token
    |--------------------------------------------------------------------------
    |
    | Your Telegram Bot API token obtained from @BotFather.
    |
    */
    'token' => env('TELEGRAM_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | The URL Telegram will POST updates to, and a secret token
    | used to verify incoming webhook requests.
    |
    */
    'webhook' => [
        'url'    => rtrim(env('TELEGRAM_DOMAIN', ''), '/') . '/webhook/' . env('TELEGRAM_BOT_TOKEN', ''),
        'secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin / Owner
    |--------------------------------------------------------------------------
    |
    | The Telegram user ID of the bot owner / admin, and an optional
    | channel or group ID where the bot should send logs.
    |
    */
    'admin_id'    => env('TELEGRAM_ADMIN_ID', ''),
    'log_channel' => env('TELEGRAM_LOG_CHANNEL', ''),

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Base Telegram API URL, request mode, and timeout values.
    |
    */
    'api_url'         => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    'async_requests'  => env('TELEGRAM_ASYNC_REQUESTS', false),
    'http_timeout'    => env('TELEGRAM_HTTP_TIMEOUT', 30),
    'connect_timeout' => env('TELEGRAM_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Proxy
    |--------------------------------------------------------------------------
    |
    | Optional SOCKS5/HTTP proxy for outgoing requests.
    | Example: socks5://user:pass@host:port
    |
    */
    'proxy' => env('TELEGRAM_PROXY', ''),

    /*
    |--------------------------------------------------------------------------
    | Commands Directory
    |--------------------------------------------------------------------------
    |
    | Directory where bot command classes are auto-discovered.
    | All classes extending Command in this directory are registered automatically.
    |
    */
    'commands_path' => base_path('Classes/Commands/Users'),

    /*
    |--------------------------------------------------------------------------
    | Parse Mode
    |--------------------------------------------------------------------------
    |
    | Default parse mode for outgoing messages: HTML, Markdown, MarkdownV2.
    |
    */
    'parse_mode' => 'HTML',

    /*
    |--------------------------------------------------------------------------
    | Disable Web Page Preview
    |--------------------------------------------------------------------------
    */
    'disable_web_preview' => false,

    /*
    |--------------------------------------------------------------------------
    | Disable Notification
    |--------------------------------------------------------------------------
    */
    'disable_notification' => false,

];

