<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';

    protected string $description = 'Start the bot';

    protected string $pattern = '{referral}';

    protected array $aliases = ['🏠 Home', 'menu'];

    protected array $callbackAliases = ['start', 'home'];

    public function handle(): void
    {
        if ($this->isCallbackQuery()) {
            $this->answerCallback();
        }

        $fallbackName = $this->getUpdate()->getMessage()->from->firstName ?? 'there';
        $name = $this->argument('referral', $fallbackName);

        $this->replyWithMessage([
            'text' => "👋 Hello, <b>{$name}</b>!\n\nWelcome to the bot. Use /help to see available commands.",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📖 Help', 'callback_data' => 'help'],
                        ['text' => '⚙️ Settings', 'callback_data' => 'settings'],
                    ],
                ],
            ]),
        ]);
    }
}
