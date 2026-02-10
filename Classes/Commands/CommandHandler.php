<?php

namespace Classes\Commands;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Classes\Log;
use Classes\Commands\Users\HelpCommand;
use Database\Models\User;

class CommandHandler
{
    /**
     * The Telegram Bot SDK Api instance.
     */
    private Api $telegram;

    /**
     * Bot config array.
     */
    private array $config;

    /**
     * Registered command instances.
     */
    private array $commands = [];

    /**
     * Directories to scan for command classes.
     */
    private array $commandDirs = [];

    public function __construct()
    {
        $this->config = require config_path('bot.php');

        $this->telegram = new Api(
            $this->config['token'],
            (bool) ($this->config['async_requests'] ?? false)
        );

        if (!empty($this->config['api_url']) && $this->config['api_url'] !== 'https://api.telegram.org') {
            $this->telegram->setBaseBotUrl($this->config['api_url']);
        }

        $timeout = (int) ($this->config['http_timeout'] ?? 30);
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 10);
        $this->telegram->setTimeOut($timeout);
        $this->telegram->setConnectTimeOut($connectTimeout);

        // Default command directory (from config or fallback)
        $this->commandDirs = [
            $this->config['commands_path'] ?? base_path('Classes/Commands/Users'),
        ];

        $this->autoRegisterCommands();
    }

    /**
     * Add a directory to scan for command classes.
     */
    public function addCommandDirectory(string $dir): self
    {
        $this->commandDirs[] = $dir;
        return $this;
    }

    /**
     * Auto-discover and register all command classes from configured directories.
     */
    private function autoRegisterCommands(): void
    {
        foreach ($this->commandDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . '/*.php');

            foreach ($files as $file) {
                $className = $this->resolveClassName($file);

                if ($className && class_exists($className)) {
                    $ref = new \ReflectionClass($className);

                    if (!$ref->isAbstract() && $ref->isSubclassOf(Command::class)) {
                        $instance = new $className();

                        // Inject handler reference into HelpCommand
                        if ($instance instanceof HelpCommand) {
                            $instance->setHandler($this);
                        }

                        $this->commands[] = $instance;
                    }
                }
            }
        }
    }

    /**
     * Resolve a PSR-4 class name from a file path.
     */
    private function resolveClassName(string $filePath): ?string
    {
        $basePath = base_path('Classes/Commands/');
        $relative = str_replace([$basePath, '.php'], '', $filePath);
        $relative = str_replace(['/', '\\'], '\\', $relative);

        return 'Classes\\Commands\\' . $relative;
    }

    /**
     * Get all registered commands.
     *
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the Telegram Bot SDK Api instance.
     */
    public function getTelegram(): Api
    {
        return $this->telegram;
    }

    /**
     * Get the bot config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Listen for incoming webhook updates and dispatch to matched commands.
     */
    public function listen(): void
    {
        $body = file_get_contents('php://input');

        if (empty($body)) {
            http_response_code(200);
            echo json_encode(['ok' => true]);
            return;
        }

        // Verify webhook secret if configured
        $secret = $this->config['webhook']['secret'] ?? '';
        if (!empty($secret)) {
            $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($headerSecret !== $secret) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $update = new Update($data);
            $this->processUpdate($update);
        } catch (\Throwable $e) {
            Log::error('Failed to process update: ' . $e->getMessage());
        }

        http_response_code(200);
        echo json_encode(['ok' => true]);
    }

    /**
     * Process an Update object and dispatch to matching commands.
     */
    public function processUpdate(Update $update): void
    {
        $type = $update->detectType();

        // Save user to database
        $this->saveUser($update);

        // Handle text messages
        if ($type === 'message' && $update->message) {
            $text = $update->message->text ?? '';

            if ($text !== '') {
                foreach ($this->commands as $command) {
                    if (!in_array('message', $command->getTriggers())) {
                        continue;
                    }

                    if ($command->matchesMessage($text)) {
                        $command->boot($this->telegram, $update, $this->config);
                        $command->handle();
                        return;
                    }
                }

                // If text starts with "/" and no command matched, send unknown command message
                if (str_starts_with($text, '/')) {
                    $chatId = $update->message->chat->id;
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => '❌ Unknown command. Use /help to see available commands.',
                    ]);
                    return;
                }
            }
        }

        // Handle callback queries
        if ($type === 'callback_query' && $update->callbackQuery) {
            $data = $update->callbackQuery->data ?? '';

            if ($data !== '') {
                foreach ($this->commands as $command) {
                    if (!in_array('callback_query', $command->getTriggers())) {
                        continue;
                    }

                    if ($command->matchesCallback($data)) {
                        $command->boot($this->telegram, $update, $this->config);
                        $command->setCallbackData($data);
                        $command->handle();
                        return;
                    }
                }

                // No callback handler matched
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $update->callbackQuery->id,
                    'text' => '❌ Unknown action.',
                    'show_alert' => false,
                ]);
                return;
            }
        }

        // Handle inline queries
        if ($type === 'inline_query' && $update->inlineQuery) {
            $query = $update->inlineQuery->query ?? '';

            foreach ($this->commands as $command) {
                if (!in_array('inline_query', $command->getTriggers())) {
                    continue;
                }

                if ($command->matchesInline($query)) {
                    $command->boot($this->telegram, $update, $this->config);
                    $command->setInlineQuery($query);
                    $command->handle();
                    return;
                }
            }
        }

        // Handle edited messages
        if ($type === 'edited_message' && $update->editedMessage) {
            $text = $update->editedMessage->text ?? '';

            foreach ($this->commands as $command) {
                if (!in_array('edited_message', $command->getTriggers())) {
                    continue;
                }

                if ($text !== '' && $command->matchesMessage($text)) {
                    $command->boot($this->telegram, $update, $this->config);
                    $command->handle();
                    return;
                }
            }
        }

        // Handle other update types via triggers
        $triggerTypes = [
            'channel_post', 'edited_channel_post', 'chat_member',
            'my_chat_member', 'chat_join_request', 'pre_checkout_query',
            'shipping_query', 'poll', 'poll_answer',
        ];

        if (in_array($type, $triggerTypes)) {
            foreach ($this->commands as $command) {
                if (in_array($type, $command->getTriggers())) {
                    $command->boot($this->telegram, $update, $this->config);
                    $command->handle();
                    return;
                }
            }
        }
    }

    /**
     * Save or update user in database from the update.
     */
    private function saveUser(Update $update): void
    {
        $from = null;

        if ($update->message) {
            $from = $update->message->from;
        } elseif ($update->callbackQuery) {
            $from = $update->callbackQuery->from;
        } elseif ($update->inlineQuery) {
            $from = $update->inlineQuery->from;
        } elseif ($update->editedMessage) {
            $from = $update->editedMessage->from;
        }

        if ($from && !empty($from->id)) {
            try {
                User::fromTelegram($from);
            } catch (\Throwable $e) {
                Log::error('Failed to save user: ' . $e->getMessage());
            }
        }
    }
}
