<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Classes\Commands\CommandHandler;

class HelpCommand extends Command
{
    protected string $name = 'help';

    protected string $description = 'Show available commands';

    protected array $callbackAliases = ['help'];

    /**
     * Reference to the handler for listing commands.
     */
    private ?CommandHandler $handler = null;

    public function setHandler(CommandHandler $handler): void
    {
        $this->handler = $handler;
    }

    public function handle(): void
    {
        if ($this->isCallbackQuery()) {
            $this->answerCallback();
        }

        $commands = $this->handler ? $this->handler->getCommands() : [];
        $lines = ["📖 <b>Available Commands</b>\n"];

        foreach ($commands as $cmd) {
            $name = $cmd->getName();
            $desc = $cmd->getDescription();
            if ($name && $desc) {
                $lines[] = "/{$name} — {$desc}";
            }
        }

        $text = implode("\n", $lines);

        if ($this->isCallbackQuery()) {
            $this->editMessage($text, [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🔙 Back', 'callback_data' => 'start']],
                    ],
                ]),
            ]);
        } else {
            $this->replyWithMessage(['text' => $text]);
        }
    }
}
