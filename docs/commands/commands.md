# Sky Telegram Bot SDK — Commands System Guide

The command system is the core of your Telegram bot. Commands are PHP classes that handle incoming updates — messages, callback queries, inline queries, and more. They are **auto-discovered** and require no manual registration.

---

## Table of Contents

- [How It Works](#how-it-works)
- [Creating a Command](#creating-a-command)
- [Command Properties](#command-properties)
- [Arguments & Patterns](#arguments--patterns)
- [Aliases & Matching](#aliases--matching)
- [Callback Queries](#callback-queries)
- [Inline Queries](#inline-queries)
- [Triggers (Update Types)](#triggers-update-types)
- [Context Helpers](#context-helpers)
- [Response Methods](#response-methods)
- [Accessing the SDK](#accessing-the-sdk)
- [Auto-Registration](#auto-registration)
- [Full Example](#full-example)

---

## How It Works

```
Telegram → POST /webhook/{token} → Router → CommandHandler → Command::handle()
```

1. Telegram sends an update to your webhook URL
2. The `CommandHandler` receives the update
3. It iterates through auto-registered commands
4. The first command that **matches** the update is executed
5. The command's `handle()` method runs your logic

---

## Creating a Command

Create a PHP file in `Classes/Commands/Users/` — it will be auto-registered.

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;

class PingCommand extends Command
{
    protected string $name = 'ping';
    protected string $description = 'Check if the bot is alive';

    public function handle(): void
    {
        $this->replyWithMessage([
            'text' => '🏓 Pong!'
        ]);
    }
}
```

That's it. Send `/ping` to your bot and it responds with "🏓 Pong!".

---

## Command Properties

| Property           | Type     | Default                          | Description                                           |
|--------------------|----------|----------------------------------|-------------------------------------------------------|
| `$name`            | `string` | `''`                             | The bot command without `/` (e.g. `start`)            |
| `$description`     | `string` | `''`                             | Shown in `/help` and BotFather                        |
| `$pattern`         | `string` | `''`                             | Named argument pattern (e.g. `{username} {age: \d+}`) |
| `$aliases`         | `array`  | `[]`                             | Text messages that trigger this command               |
| `$callbackAliases` | `array`  | `[]`                             | Callback data patterns (supports `*` wildcard)        |
| `$inlineAliases`   | `array`  | `[]`                             | Inline query patterns (supports `*` wildcard)         |
| `$triggers`        | `array`  | `['message', 'callback_query']`  | Update types this command listens to                  |

---

## Arguments & Patterns

### Without Pattern (positional)

If no `$pattern` is defined, arguments are stored as an indexed array:

```php
class GreetCommand extends Command
{
    protected string $name = 'greet';

    public function handle(): void
    {
        $name = $this->argument(0, 'stranger');
        $this->replyWithMessage(['text' => "Hello, {$name}!"]);
    }
}
```

`/greet John` → `Hello, John!`
`/greet` → `Hello, stranger!`

### With Named Pattern

Define `$pattern` to name your arguments:

```php
class GreetCommand extends Command
{
    protected string $name = 'greet';
    protected string $pattern = '{username}';

    public function handle(): void
    {
        $username = $this->argument('username', 'stranger');
        $this->replyWithMessage(['text' => "Hello, {$username}!"]);
    }
}
```

`/greet Alice` → `Hello, Alice!`

### Multiple Named Arguments

```php
class RegisterCommand extends Command
{
    protected string $name = 'register';
    protected string $pattern = '{username} {age: \d+}';

    public function handle(): void
    {
        $username = $this->argument('username');
        $age = $this->argument('age');

        if (!$username) {
            $this->replyWithMessage(['text' => 'Please provide your username. Ex: /register john 25']);
            return;
        }

        if (!$age) {
            $this->replyWithMessage(['text' => 'Please provide your age. Ex: /register john 25']);
            return;
        }

        $this->replyWithMessage(['text' => "Welcome {$username}, age {$age}!"]);
    }
}
```

`/register alice 25` → `Welcome alice, age 25!`
`/register alice abc` → age is `null` (failed `\d+` validation)

### Pattern Syntax

```
{name}              — Captures any word
{name: regex}       — Captures and validates against regex
```

**Examples:**
```php
'{username}'                     // any single word
'{username} {age: \d+}'         // word + digits only
'{action: start|stop|restart}'  // only those values
'{id: [0-9a-f]{8}}'            // hex string, 8 chars
```

### `argument()` Method

```php
$this->argument()                    // All arguments as array
$this->argument('username')          // Named argument (null if missing)
$this->argument('username', 'guest') // Named argument with default
$this->argument(0)                   // First argument by index
$this->argument(0, 'default')        // First argument with default
```

Other helpers:
```php
$this->argumentString()    // All arguments joined as a string
$this->hasArguments()      // Boolean: any arguments provided?
```

---

## Aliases & Matching

### Text Aliases

Trigger a command from plain text messages (case-insensitive):

```php
class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['🏠 Home', 'menu', 'back to start'];
}
```

All of these trigger the start command:
- `/start`
- `🏠 Home`
- `MENU`
- `Back to Start`

### Callback Aliases (with wildcards)

Match callback data from inline keyboard buttons:

```php
class SettingsCommand extends Command
{
    protected string $name = 'settings';
    protected array $callbackAliases = ['settings', 'setting_*'];
}
```

Matches: `settings`, `setting_theme`, `setting_language`, etc.

The matched callback data is available via `$this->callbackData`:

```php
public function handle(): void
{
    $data = $this->callbackData; // e.g. "setting_theme"
}
```

### Inline Aliases (with wildcards)

Match inline query text:

```php
class SearchCommand extends Command
{
    protected string $name = '';
    protected array $inlineAliases = ['search *'];
    protected array $triggers = ['inline_query'];
}
```

---

## Callback Queries

Handle inline keyboard button presses:

```php
class ConfirmCommand extends Command
{
    protected string $name = '';
    protected array $callbackAliases = ['confirm_*', 'cancel_*'];
    protected array $triggers = ['callback_query'];

    public function handle(): void
    {
        $data = $this->callbackData; // "confirm_123" or "cancel_456"

        // Always answer the callback to remove the loading spinner
        $this->answerCallback('Done!');

        if (str_starts_with($data, 'confirm_')) {
            $id = str_replace('confirm_', '', $data);
            $this->editMessage("✅ Confirmed #{$id}");
        } else {
            $this->editMessage("❌ Cancelled");
        }
    }
}
```

### `answerCallback()` Params

```php
$this->answerCallback();                          // Silent dismiss
$this->answerCallback('Saved!');                   // Toast notification
$this->answerCallback('Are you sure?', true);      // Alert popup
```

---

## Inline Queries

Handle `@yourbot <query>` searches:

```php
class InlineSearchCommand extends Command
{
    protected string $name = '';
    protected array $inlineAliases = ['*'];
    protected array $triggers = ['inline_query'];

    public function handle(): void
    {
        $query = $this->inlineQuery;

        // Use the SDK directly for inline results
        $this->telegram->answerInlineQuery([
            'inline_query_id' => $this->update->inlineQuery->id,
            'results' => json_encode([
                [
                    'type' => 'article',
                    'id' => '1',
                    'title' => "Result for: {$query}",
                    'input_message_content' => [
                        'message_text' => "You searched: {$query}",
                    ],
                ],
            ]),
        ]);
    }
}
```

---

## Triggers (Update Types)

By default, commands listen to `message` and `callback_query`. Override `$triggers` to listen to other update types:

```php
protected array $triggers = ['message', 'callback_query'];
```

**Available triggers:**

| Trigger               | Description                        |
|-----------------------|------------------------------------|
| `message`             | New text/media message             |
| `callback_query`      | Inline keyboard button press       |
| `inline_query`        | Inline bot query                   |
| `edited_message`      | User edited a message              |
| `channel_post`        | New post in a channel              |
| `edited_channel_post` | Edited channel post                |
| `chat_member`         | Chat member status changed         |
| `my_chat_member`      | Bot's own membership changed       |
| `chat_join_request`   | User wants to join a chat          |
| `pre_checkout_query`  | Payment pre-checkout               |
| `shipping_query`      | Payment shipping query             |
| `poll`                | Poll state changed                 |
| `poll_answer`         | User answered a poll               |

### Example: New Member Handler

```php
class WelcomeNewMemberCommand extends Command
{
    protected string $name = '';
    protected array $triggers = ['chat_member'];

    public function handle(): void
    {
        $member = $this->update->chatMember;
        // Handle new member join logic
    }
}
```

---

## Context Helpers

Access information about the current update:

```php
// User & Chat
$this->getFrom()           // User object (from field)
$this->getUserId()         // User's Telegram ID
$this->getChat()           // Chat object
$this->getChatId()         // Chat ID

// Message
$this->getMessage()        // Message object
$this->getMessageText()    // Message text content
$this->getMessageId()      // Message ID

// Update
$this->getUpdate()         // Full Update object
$this->isCallbackQuery()   // Is this a callback query?
$this->isInlineQuery()     // Is this an inline query?

// Matched data
$this->callbackData        // Matched callback data string
$this->inlineQuery         // Matched inline query string
```

---

## Response Methods

All methods auto-fill `chat_id` and `parse_mode` from config.

### Text Messages

```php
// Simple reply
$this->reply('Hello!');

// Reply with extra options
$this->reply('Hello!', [
    'disable_notification' => true
]);

// SDK-style (pass sendMessage params directly)
$this->replyWithMessage([
    'text' => 'Hello!',
    'reply_markup' => json_encode([
        'inline_keyboard' => [
            [['text' => 'Click me', 'callback_data' => 'clicked']]
        ]
    ])
]);
```

### Inline Keyboard

```php
$this->replyWithKeyboard('Choose an option:', [
    [
        ['text' => '✅ Yes', 'callback_data' => 'yes'],
        ['text' => '❌ No', 'callback_data' => 'no'],
    ],
    [
        ['text' => '🔙 Back', 'callback_data' => 'back'],
    ],
]);
```

### Edit Messages

```php
// Edit text
$this->editMessage('Updated text!');

// Edit text with new keyboard
$this->editMessage('Updated!', [
    'reply_markup' => json_encode([
        'inline_keyboard' => [
            [['text' => 'New Button', 'callback_data' => 'new']]
        ]
    ])
]);

// Edit keyboard only
$this->editKeyboard([
    [['text' => 'Changed', 'callback_data' => 'changed']]
]);
```

### Media

```php
// Photo (shorthand)
$this->replyWithPhoto('https://example.com/photo.jpg', 'Caption here');

// Photo (SDK-style)
$this->replyWithPhoto([
    'photo' => 'https://example.com/photo.jpg',
    'caption' => 'Caption here',
]);

// Document
$this->replyWithDocument('/path/to/file.pdf', 'Your file');

// Video
$this->replyWithVideo('https://example.com/video.mp4', 'Video caption');

// Voice
$this->replyWithVoice('https://example.com/voice.ogg');

// Sticker
$this->replyWithSticker('sticker_file_id');

// Location
$this->replyWithLocation([
    'latitude' => 51.5074,
    'longitude' => -0.1278,
]);

// Contact
$this->replyWithContact([
    'phone_number' => '+1234567890',
    'first_name' => 'John',
]);
```

### Actions & Other

```php
// Send "typing..." indicator
$this->sendAction('typing');

// SDK-style chat action
$this->replyWithChatAction(['action' => 'upload_photo']);

// Delete message
$this->deleteMessage();

// Forward message to another chat
$this->forwardTo($chatId);

// Answer callback query
$this->answerCallback();                      // Silent
$this->answerCallback('Saved!');              // Toast
$this->answerCallback('Sure?', true);         // Alert
```

---

## Accessing the SDK

For any Telegram API method not covered by shortcuts, use the SDK directly:

```php
public function handle(): void
{
    $telegram = $this->getTelegram();

    // Any Telegram Bot API method
    $telegram->sendMessage([
        'chat_id' => $this->getChatId(),
        'text' => 'Direct SDK call',
    ]);

    $telegram->sendPoll([
        'chat_id' => $this->getChatId(),
        'question' => 'Favorite color?',
        'options' => ['Red', 'Blue', 'Green'],
    ]);
}
```

---

## Auto-Registration

Commands are auto-discovered from `Classes/Commands/Users/`. Any PHP class in that directory that:

1. Extends `Classes\Commands\Command`
2. Is **not** abstract

...is automatically registered. No config needed.

The scan directory is set in `config/bot.php`:

```php
'commands_path' => base_path('Classes/Commands/Users'),
```

### File → Class Resolution

| File Path                                      | Class Name                               |
|------------------------------------------------|------------------------------------------|
| `Classes/Commands/Users/StartCommand.php`      | `Classes\Commands\Users\StartCommand`    |
| `Classes/Commands/Users/PingCommand.php`       | `Classes\Commands\Users\PingCommand`     |
| `Classes/Commands/Users/SettingsCommand.php`   | `Classes\Commands\Users\SettingsCommand` |

### Dispatch Order

Commands are matched in the order they are discovered (alphabetical by filename). The **first match wins** — subsequent commands are not checked.

---

## Full Example

A complete command handling a `/profile` command, callback buttons, and named arguments:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\User;

class ProfileCommand extends Command
{
    protected string $name = 'profile';
    protected string $description = 'View your profile';
    protected string $pattern = '{user_id: \d+}';
    protected array $callbackAliases = ['profile', 'profile_edit', 'profile_back'];

    public function handle(): void
    {
        if ($this->isCallbackQuery()) {
            $this->answerCallback();

            return match ($this->callbackData) {
                'profile_edit' => $this->showEditForm(),
                'profile_back' => $this->showProfile(),
                default        => $this->showProfile(),
            };
        }

        $this->showProfile();
    }

    private function showProfile(): void
    {
        // Use named arg or fall back to sender's ID
        $targetId = $this->argument('user_id', $this->getUserId());

        $user = User::findByTelegramId($targetId);
        if (!$user) {
            $this->replyWithMessage(['text' => '❌ User not found.']);
            return;
        }

        $text = "👤 <b>Profile</b>\n\n"
              . "Name: {$user->first_name}\n"
              . "Username: @{$user->username}\n"
              . "Joined: {$user->created_at->format('M d, Y')}";

        $keyboard = [
            [['text' => '✏️ Edit', 'callback_data' => 'profile_edit']],
        ];

        if ($this->isCallbackQuery()) {
            $this->editMessage($text, [
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
            ]);
        } else {
            $this->replyWithKeyboard($text, $keyboard);
        }
    }

    private function showEditForm(): void
    {
        $this->editMessage("✏️ <b>Edit Profile</b>\n\nSend your new name:", [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🔙 Back', 'callback_data' => 'profile_back']],
                ],
            ]),
        ]);
    }
}
```
