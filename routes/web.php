<?php

/**
 * Web Routes
 *
 * Define all application routes here.
 * The router supports {param} and {param:regex} placeholders.
 *
 * Available methods: $router->get(), $router->post(), $router->any()
 */

use Classes\Commands\CommandHandler;

// ── Webhook Route ──────────────────────────────────────────────────

$router->post('/webhook/{token}', function (string $token) {
    $config = require config_path('bot.php');

    // Validate the token matches our bot
    if ($token !== $config['token']) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        return;
    }

    $handler = new CommandHandler();
    $handler->listen();
});

// ── Health Check ───────────────────────────────────────────────────

$router->get('/', function () {
    json_response([
        'status' => 'ok',
        'name'   => 'Sky Telegram Bot SDK',
        'time'   => now(),
    ]);
});
