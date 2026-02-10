<?php

namespace Classes\Commands;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;

abstract class Command
{
    /**
     * The bot command (e.g., 'start', 'help').
     * Set to empty string for non-command handlers.
     */
    protected string $name = '';

    /**
     * Command description (shown in /help or BotFather).
     */
    protected string $description = '';

    /**
     * Named argument pattern.
     * Define expected arguments with optional regex validation.
     * Example: '{username}' or '{username} {age: \d+}'
     */
    protected string $pattern = '';

    /**
     * Text aliases that trigger this command.
     * Example: ['🏠 Home', 'menu', 'start']
     */
    protected array $aliases = [];

    /**
     * Callback data patterns that trigger this command.
     * Supports exact match and prefix match with wildcard (*).
     * Example: ['settings', 'page_*', 'action:confirm']
     */
    protected array $callbackAliases = [];

    /**
     * Inline query patterns that trigger this command.
     */
    protected array $inlineAliases = [];

    /**
     * The update types this command listens to.
     * Possible: 'message', 'callback_query', 'inline_query',
     *           'edited_message', 'channel_post', 'chat_member',
     *           'my_chat_member', 'chat_join_request', 'pre_checkout_query',
     *           'shipping_query', 'poll', 'poll_answer'
     */
    protected array $triggers = ['message', 'callback_query'];

    /**
     * The Telegram Bot SDK Api instance.
     */
    protected Api $telegram;

    /**
     * The raw Telegram update object.
     */
    protected Update $update;

    /**
     * Parsed command arguments.
     */
    protected array $arguments = [];

    /**
     * The matched callback data (for callback queries).
     */
    protected string $callbackData = '';

    /**
     * The matched inline query (for inline queries).
     */
    protected string $inlineQuery = '';

    /**
     * Bot config array.
     */
    protected array $config = [];

    // ── Abstract Handler ───────────────────────────────────────────

    /**
     * Handle the command.
     */
    abstract public function handle(): void;

    // ── Initialization ─────────────────────────────────────────────

    public function boot(Api $telegram, Update $update, array $config = []): void
    {
        $this->telegram = $telegram;
        $this->update   = $update;
        $this->config   = $config;
    }

    public function setArguments(array $args): void
    {
        $this->arguments = $args;
    }

    public function setCallbackData(string $data): void
    {
        $this->callbackData = $data;
    }

    public function setInlineQuery(string $query): void
    {
        $this->inlineQuery = $query;
    }

    // ── Getters ────────────────────────────────────────────────────

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getCallbackAliases(): array
    {
        return $this->callbackAliases;
    }

    public function getInlineAliases(): array
    {
        return $this->inlineAliases;
    }

    public function getTriggers(): array
    {
        return $this->triggers;
    }

    // ── Argument Helpers ───────────────────────────────────────────

    /**
     * Get a named argument by key, a positional argument by index, or all arguments.
     *
     * Usage:
     *   $this->argument('username')           // named arg
     *   $this->argument('username', 'guest')   // named arg with default
     *   $this->argument(0)                     // positional (0-based)
     *   $this->argument()                      // all arguments as array
     */
    public function argument(string|int|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->arguments;
        }

        if (is_string($key)) {
            return $this->arguments[$key] ?? $default;
        }

        // Integer index — get values only (positional access)
        $values = array_values($this->arguments);
        return $values[$key] ?? $default;
    }

    /**
     * Get all arguments as a single string.
     */
    public function argumentString(): string
    {
        return implode(' ', array_values($this->arguments));
    }

    /**
     * Check if arguments exist.
     */
    public function hasArguments(): bool
    {
        return !empty($this->arguments);
    }

    /**
     * Parse the pattern definition into an array of argument definitions.
     * Each entry: ['name' => string, 'regex' => string|null]
     *
     * @return array<int, array{name: string, regex: string|null}>
     */
    protected function parsePattern(): array
    {
        if (empty($this->pattern)) {
            return [];
        }

        preg_match_all('/\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*(?::\s*(.+?))?\s*\}/', $this->pattern, $matches, PREG_SET_ORDER);

        $params = [];
        foreach ($matches as $match) {
            $params[] = [
                'name'  => $match[1],
                'regex' => isset($match[2]) ? trim($match[2]) : null,
            ];
        }

        return $params;
    }

    /**
     * Parse raw argument tokens against the pattern and store as named arguments.
     */
    protected function bindArguments(array $tokens): void
    {
        $params = $this->parsePattern();

        if (empty($params)) {
            // No pattern — store as indexed array
            $this->arguments = $tokens;
            return;
        }

        $this->arguments = [];

        foreach ($params as $i => $param) {
            $value = $tokens[$i] ?? null;

            if ($value !== null && $param['regex'] !== null) {
                // Validate against regex; discard if invalid
                if (!preg_match('/^(?:' . $param['regex'] . ')$/', $value)) {
                    $value = null;
                }
            }

            $this->arguments[$param['name']] = $value;
        }
    }

    // ── Context Helpers ────────────────────────────────────────────

    /**
     * Get the chat object from the update.
     */
    public function getChat(): mixed
    {
        return $this->getMessage()?->chat ?? null;
    }

    /**
     * Get the chat ID.
     */
    public function getChatId(): ?int
    {
        $chat = $this->getChat();
        return $chat ? $chat->id : null;
    }

    /**
     * Get the from (user) object from the update.
     */
    public function getFrom(): mixed
    {
        if ($this->update->callbackQuery) {
            return $this->update->callbackQuery->from ?? null;
        }
        if ($this->update->inlineQuery) {
            return $this->update->inlineQuery->from ?? null;
        }
        return $this->getMessage()?->from ?? null;
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): ?int
    {
        $from = $this->getFrom();
        return $from ? $from->id : null;
    }

    /**
     * Get the message object.
     */
    public function getMessage(): mixed
    {
        return $this->update->getMessage();
    }

    /**
     * Get the message text.
     */
    public function getMessageText(): string
    {
        $msg = $this->getMessage();
        return $msg ? ($msg->text ?? '') : '';
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?int
    {
        $msg = $this->getMessage();
        return $msg ? ($msg->messageId ?? null) : null;
    }

    /**
     * Check if update is a callback query.
     */
    public function isCallbackQuery(): bool
    {
        return $this->update->callbackQuery !== null;
    }

    /**
     * Check if update is an inline query.
     */
    public function isInlineQuery(): bool
    {
        return $this->update->inlineQuery !== null;
    }

    // ── Matching Logic ─────────────────────────────────────────────

    /**
     * Check if a text message matches this command.
     */
    public function matchesMessage(string $text): bool
    {
        // Match /command or /command@botname
        if ($this->name && preg_match('/^\/(' . preg_quote($this->name, '/') . ')(?:@\S+)?(?:\s+(.*))?$/is', $text, $m)) {
            $tokens = isset($m[2]) ? preg_split('/\s+/', trim($m[2])) : [];
            if ($tokens === ['']) $tokens = [];
            $this->bindArguments($tokens);
            return true;
        }

        // Match text aliases (case-insensitive)
        foreach ($this->aliases as $alias) {
            if (mb_strtolower($text) === mb_strtolower($alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a callback data matches this command.
     */
    public function matchesCallback(string $data): bool
    {
        // Match command name as callback
        if ($this->name && $data === $this->name) {
            $this->callbackData = $data;
            return true;
        }

        foreach ($this->callbackAliases as $pattern) {
            if ($this->matchPattern($pattern, $data)) {
                $this->callbackData = $data;
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an inline query matches this command.
     */
    public function matchesInline(string $query): bool
    {
        foreach ($this->inlineAliases as $pattern) {
            if ($this->matchPattern($pattern, $query)) {
                $this->inlineQuery = $query;
                return true;
            }
        }

        return false;
    }

    /**
     * Match a pattern with wildcard (*) support.
     * 'page_*' matches 'page_1', 'page_abc', etc.
     */
    private function matchPattern(string $pattern, string $value): bool
    {
        if ($pattern === $value) {
            return true;
        }

        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
            return (bool) preg_match($regex, $value);
        }

        return false;
    }

    /**
     * Get the Update object.
     */
    public function getUpdate(): Update
    {
        return $this->update;
    }

    // ── Response Shortcuts (using Telegram Bot SDK) ────────────────

    /**
     * Send a text reply to the current chat.
     */
    public function reply(string $text, array $extra = []): Message
    {
        $params = array_merge([
            'chat_id'    => $this->getChatId(),
            'text'       => $text,
            'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
        ], $extra);

        return $this->telegram->sendMessage($params);
    }

    /**
     * Reply with a message (SDK-compatible style).
     * Accepts sendMessage parameters; chat_id and parse_mode auto-filled.
     */
    public function replyWithMessage(array $params): Message
    {
        $params = array_merge([
            'chat_id'    => $this->getChatId(),
            'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
        ], $params);

        return $this->telegram->sendMessage($params);
    }

    /**
     * Reply with inline keyboard.
     */
    public function replyWithKeyboard(string $text, array $keyboard, array $extra = []): Message
    {
        return $this->reply($text, array_merge([
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ], $extra));
    }

    /**
     * Edit the current message text (for callback queries).
     */
    public function editMessage(string $text, array $extra = []): Message
    {
        $params = array_merge([
            'chat_id'    => $this->getChatId(),
            'message_id' => $this->getMessageId(),
            'text'       => $text,
            'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
        ], $extra);

        return $this->telegram->editMessageText($params);
    }

    /**
     * Edit the current message inline keyboard.
     */
    public function editKeyboard(array $keyboard, array $extra = []): Message
    {
        $params = array_merge([
            'chat_id'    => $this->getChatId(),
            'message_id' => $this->getMessageId(),
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ], $extra);

        return $this->telegram->editMessageReplyMarkup($params);
    }

    /**
     * Answer a callback query.
     */
    public function answerCallback(string $text = '', bool $alert = false, array $extra = []): bool
    {
        if (!$this->isCallbackQuery()) return false;

        $params = array_merge([
            'callback_query_id' => $this->update->callbackQuery->id,
            'text'              => $text,
            'show_alert'        => $alert,
        ], $extra);

        return $this->telegram->answerCallbackQuery($params);
    }

    /**
     * Delete the current message.
     */
    public function deleteMessage(): mixed
    {
        return $this->telegram->deleteMessage([
            'chat_id'    => $this->getChatId(),
            'message_id' => $this->getMessageId(),
        ]);
    }

    /**
     * Send a photo (SDK-compatible: accepts array or shorthand).
     */
    public function replyWithPhoto(string|array $params, string $caption = '', array $extra = []): Message
    {
        if (is_array($params)) {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $params);
        } else {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'photo'      => $params,
                'caption'    => $caption,
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $extra);
        }

        return $this->telegram->sendPhoto($params);
    }

    /**
     * Send a document (SDK-compatible: accepts array or shorthand).
     */
    public function replyWithDocument(string|array $params, string $caption = '', array $extra = []): Message
    {
        if (is_array($params)) {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $params);
        } else {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'document'   => $params,
                'caption'    => $caption,
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $extra);
        }

        return $this->telegram->sendDocument($params);
    }

    /**
     * Forward a message.
     */
    public function forwardTo(int $chatId): Message
    {
        return $this->telegram->forwardMessage([
            'chat_id'      => $chatId,
            'from_chat_id' => $this->getChatId(),
            'message_id'   => $this->getMessageId(),
        ]);
    }

    /**
     * Send a chat action (typing, upload_photo, etc.).
     */
    public function sendAction(string $action = 'typing'): bool
    {
        return $this->telegram->sendChatAction([
            'chat_id' => $this->getChatId(),
            'action'  => $action,
        ]);
    }

    /**
     * Send a sticker.
     */
    public function replyWithSticker(string|array $params): Message
    {
        if (is_array($params)) {
            $params = array_merge(['chat_id' => $this->getChatId()], $params);
        } else {
            $params = ['chat_id' => $this->getChatId(), 'sticker' => $params];
        }

        return $this->telegram->sendSticker($params);
    }

    /**
     * Send a location.
     */
    public function replyWithLocation(array $params): Message
    {
        $params = array_merge(['chat_id' => $this->getChatId()], $params);

        return $this->telegram->sendLocation($params);
    }

    /**
     * Send a contact.
     */
    public function replyWithContact(array $params): Message
    {
        $params = array_merge(['chat_id' => $this->getChatId()], $params);

        return $this->telegram->sendContact($params);
    }

    /**
     * Send a video.
     */
    public function replyWithVideo(string|array $params, string $caption = '', array $extra = []): Message
    {
        if (is_array($params)) {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $params);
        } else {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'video'      => $params,
                'caption'    => $caption,
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $extra);
        }

        return $this->telegram->sendVideo($params);
    }

    /**
     * Send a voice message.
     */
    public function replyWithVoice(string|array $params, string $caption = '', array $extra = []): Message
    {
        if (is_array($params)) {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $params);
        } else {
            $params = array_merge([
                'chat_id'    => $this->getChatId(),
                'voice'      => $params,
                'caption'    => $caption,
                'parse_mode' => $this->config['parse_mode'] ?? 'HTML',
            ], $extra);
        }

        return $this->telegram->sendVoice($params);
    }

    /**
     * Reply with a chat action (SDK-compatible).
     */
    public function replyWithChatAction(array $params): bool
    {
        $params = array_merge(['chat_id' => $this->getChatId()], $params);

        return $this->telegram->sendChatAction($params);
    }

    /**
     * Get the Telegram Bot SDK Api instance.
     */
    public function getTelegram(): Api
    {
        return $this->telegram;
    }
}
