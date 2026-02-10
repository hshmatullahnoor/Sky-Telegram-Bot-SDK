<?php

/**
 * CLI Helper Commands
 *
 * Usage:
 *   php helper.php migrate                        Run pending migrations
 *   php helper.php migrate:down                   Rollback all migrations
 *   php helper.php migrate:fresh                  Drop all & re-run migrations
 *   php helper.php make:model <Name>              Create a new model
 *   php helper.php make:migration <name>          Create a new migration
 *   php helper.php make:model <Name> -m           Create model + migration
 *   php helper.php generate:secret                 Generate a secret token & save to .env
 *   php helper.php webhook:set                      Set the webhook
 *   php helper.php webhook:delete                   Delete the webhook
 *   php helper.php webhook:info                     Get webhook info
 *   php helper.php webhook:drop-pending             Delete pending updates
 *   php helper.php setup                              Copy .env.example to .env & generate secret
 */

require __DIR__ . '/config/bootstrap.php';

use Classes\Commands\Helper\MigrateCommand;
use Classes\Commands\Helper\WebhookCommand;
use Classes\Commands\Helper\Make\MakeModelCommand;
use Classes\Commands\Helper\Make\MakeMigrationCommand;

$command = $argv[1] ?? null;
$arg     = $argv[2] ?? '';
$flags   = array_slice($argv, 3);

if (!$command) {
    echo "Available commands:" . PHP_EOL;
    echo "  migrate                        Run pending migrations" . PHP_EOL;
    echo "  migrate:down                   Rollback all migrations" . PHP_EOL;
    echo "  migrate:fresh                  Drop all & re-run migrations" . PHP_EOL;
    echo "  make:model <Name>              Create a new model" . PHP_EOL;
    echo "  make:model <Name> -m           Create model + migration" . PHP_EOL;
    echo "  make:migration <name>          Create a new migration" . PHP_EOL;
    echo "  generate:secret                Generate secret token & save to .env" . PHP_EOL;
    echo "  webhook:set                    Set the webhook" . PHP_EOL;
    echo "  webhook:delete                 Delete the webhook" . PHP_EOL;
    echo "  webhook:info                   Get webhook info" . PHP_EOL;
    echo "  webhook:drop-pending           Delete pending updates" . PHP_EOL;
    echo "  setup                          Copy .env.example to .env & generate secret" . PHP_EOL;
    exit(0);
}

match ($command) {
    'migrate'        => (new MigrateCommand())->up(),
    'migrate:down'   => (new MigrateCommand())->down(),
    'migrate:fresh'  => (new MigrateCommand())->fresh(),
    'make:model'     => makeModel($arg, $flags),
    'make:migration' => (new MakeMigrationCommand())->handle($arg),
    'generate:secret' => generateSecret(),
    'webhook:set'          => (new WebhookCommand())->set(),
    'webhook:delete'       => (new WebhookCommand())->delete(),
    'webhook:info'         => (new WebhookCommand())->info(),
    'webhook:drop-pending' => (new WebhookCommand())->dropPending(),
    'setup'                => setup(),
    default          => print("Unknown command: {$command}" . PHP_EOL),
};

function makeModel(string $name, array $flags): void
{
    if (empty($name)) {
        echo "Usage: php helper.php make:model <Name> [-m]" . PHP_EOL;
        return;
    }

    (new MakeModelCommand())->handle($name);

    // -m flag: also create a migration
    if (in_array('-m', $flags)) {
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        (new MakeMigrationCommand())->handle("create_{$snake}s_table");
    }
}

function generateSecret(): void
{
    $token = bin2hex(random_bytes(32));
    $envPath = base_path('.env');

    if (!file_exists($envPath)) {
        echo "  ✗ .env file not found." . PHP_EOL;
        return;
    }

    $content = file_get_contents($envPath);

    if (preg_match('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', $content)) {
        $content = preg_replace('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', "TELEGRAM_WEBHOOK_SECRET={$token}", $content);
    } else {
        $content .= PHP_EOL . "TELEGRAM_WEBHOOK_SECRET={$token}" . PHP_EOL;
    }

    file_put_contents($envPath, $content);

    echo "  ✓ Secret token generated and saved to .env" . PHP_EOL;
    echo "  Token: {$token}" . PHP_EOL;
}

function setup(): void
{
    $root    = base_path();
    $envFile = $root . DIRECTORY_SEPARATOR . '.env';
    $example = $root . DIRECTORY_SEPARATOR . '.env.example';

    if (!file_exists($example)) {
        echo "  ✗ .env.example not found." . PHP_EOL;
        return;
    }

    if (file_exists($envFile)) {
        echo "  ✗ .env already exists. Delete it first if you want to re-setup." . PHP_EOL;
        return;
    }

    copy($example, $envFile);
    echo "  ✓ .env.example copied to .env" . PHP_EOL;

    // Generate secret token
    $token   = bin2hex(random_bytes(32));
    $content = file_get_contents($envFile);

    if (preg_match('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', $content)) {
        $content = preg_replace('/^TELEGRAM_WEBHOOK_SECRET=.*$/m', "TELEGRAM_WEBHOOK_SECRET={$token}", $content);
    } else {
        $content .= PHP_EOL . "TELEGRAM_WEBHOOK_SECRET={$token}" . PHP_EOL;
    }

    file_put_contents($envFile, $content);

    echo "  ✓ Secret token generated and saved to .env" . PHP_EOL;
    echo "  Token: {$token}" . PHP_EOL;
}
