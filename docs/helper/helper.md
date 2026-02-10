# Sky Telegram Bot SDK — CLI Helper Guide

The `helper.php` script is a command-line tool for managing your Sky Telegram Bot SDK project. It handles database migrations, code generation, webhook management, and initial setup.

## Usage

```bash
php helper.php <command> [arguments] [flags]
```

Running `php helper.php` without arguments will display a list of all available commands.

---

## Table of Contents

- [Setup](#setup)
- [Migrations](#migrations)
- [Code Generators](#code-generators)
- [Webhook Management](#webhook-management)
- [Secret Token](#secret-token)

---

## Setup

### `setup`

Initializes the project for first use. Copies `.env.example` to `.env` and generates a webhook secret token.

```bash
php helper.php setup
```

**Output:**
```
  ✓ .env.example copied to .env
  ✓ Secret token generated and saved to .env
  Token: a3f9c8...
```

> **Note:** This will fail if `.env` already exists. Delete it first if you need to re-setup.

After running setup, open `.env` and fill in your values:

| Variable               | Description                                  | Example                              |
|------------------------|----------------------------------------------|--------------------------------------|
| `DB_DRIVER`            | Database driver (`sqlite` or `mysql`)        | `sqlite`                             |
| `TELEGRAM_BOT_TOKEN`   | Bot token from @BotFather                    | `123456:ABC-DEF...`                  |
| `TELEGRAM_DOMAIN`      | Your server domain (with https)              | `https://example.com`                |
| `TELEGRAM_ADMIN_ID`    | Your Telegram user ID                        | `123456789`                          |

---

## Migrations

Migrations live in `database/migrations/` and are numbered sequentially (e.g., `001_create_users_table.php`).

### `migrate`

Run all pending migrations (creates tables).

```bash
php helper.php migrate
```

**Output:**
```
  ✓ Migrated: 001_create_users_table.php
  ✓ Migrated: 002_create_sessions_table.php
  All migrations completed.
```

### `migrate:down`

Rollback all migrations (drops tables in reverse order).

```bash
php helper.php migrate:down
```

**Output:**
```
  ✓ Rolled back: 002_create_sessions_table.php
  ✓ Rolled back: 001_create_users_table.php
  All migrations rolled back.
```

### `migrate:fresh`

Drop all tables and re-run all migrations from scratch. Useful during development.

```bash
php helper.php migrate:fresh
```

**Output:**
```
  Rolling back all migrations...
  ✓ Rolled back: 002_create_sessions_table.php
  ✓ Rolled back: 001_create_users_table.php
  Running migrations...
  ✓ Migrated: 001_create_users_table.php
  ✓ Migrated: 002_create_sessions_table.php
  Fresh migration completed.
```

---

## Code Generators

### `make:model <Name>`

Generate a new Eloquent model in `database/Models/`.

```bash
php helper.php make:model Post
```

Creates `database/Models/Post.php`:

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [];
}
```

### `make:model <Name> -m`

Generate a model **and** its corresponding migration file.

```bash
php helper.php make:model Comment -m
```

Creates:
- `database/Models/Comment.php`
- `database/migrations/002_create_comments_table.php` (number auto-increments)

### `make:migration <name>`

Generate a standalone migration file. The name should be descriptive using snake_case.

```bash
php helper.php make:migration create_posts_table
```

Creates `database/migrations/002_create_posts_table.php`:

```php
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        Capsule::schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('posts');
    }
};
```

> The sequence number is auto-generated based on existing migration files.

---

## Webhook Management

These commands manage the Telegram webhook connection for your bot. They use the `TELEGRAM_BOT_TOKEN`, `TELEGRAM_DOMAIN`, and `TELEGRAM_WEBHOOK_SECRET` from your `.env` file.

### `webhook:set`

Register the webhook URL with Telegram. If no secret token exists, one is auto-generated.

```bash
php helper.php webhook:set
```

**Output:**
```
  ✓ Generated new secret token and saved to .env
  ✓ Set Webhook: Success
    Webhook was set
```

The webhook URL is built automatically as: `{TELEGRAM_DOMAIN}/webhook/{TELEGRAM_BOT_TOKEN}`

### `webhook:info`

Display current webhook information from Telegram.

```bash
php helper.php webhook:info
```

**Output:**
```
Webhook Info:
  URL: https://example.com/webhook/123456:ABC-DEF...
  Custom Certificate: No
  Pending Updates: 0
  Max Connections: 40
```

### `webhook:delete`

Remove the webhook (stop receiving updates).

```bash
php helper.php webhook:delete
```

**Output:**
```
  ✓ Delete Webhook: Success
    Webhook was deleted
```

### `webhook:drop-pending`

Delete all pending updates that Telegram has queued for your bot.

```bash
php helper.php webhook:drop-pending
```

**Output:**
```
  ✓ Drop Pending Updates: Success
```

---

## Secret Token

### `generate:secret`

Generate a new 64-character hex secret token and save it to `TELEGRAM_WEBHOOK_SECRET` in `.env`. This token is used to verify that incoming webhook requests are from Telegram.

```bash
php helper.php generate:secret
```

**Output:**
```
  ✓ Secret token generated and saved to .env
  Token: b7e4a1d9c3f8...
```

---

## Quick Start Workflow

```bash
# 1. Install dependencies
composer install

# 2. Initialize the project
php helper.php setup

# 3. Edit .env with your bot token and domain
#    TELEGRAM_BOT_TOKEN=your_token_here
#    TELEGRAM_DOMAIN=https://yourdomain.com

# 4. Run migrations
php helper.php migrate

# 5. Set the webhook
php helper.php webhook:set

# 6. Create your first model
php helper.php make:model Session -m

# 7. Run the new migration
php helper.php migrate
```

Your bot is now ready to receive updates at `https://yourdomain.com/webhook/{your_bot_token}`.

---

## Command Reference

| Command                        | Description                                    |
|--------------------------------|------------------------------------------------|
| `setup`                        | Copy `.env.example` → `.env` + generate secret |
| `migrate`                      | Run all pending migrations                     |
| `migrate:down`                 | Rollback all migrations                        |
| `migrate:fresh`                | Drop all & re-run migrations                   |
| `make:model <Name>`            | Create a new Eloquent model                    |
| `make:model <Name> -m`         | Create model + migration                       |
| `make:migration <name>`        | Create a new migration file                    |
| `generate:secret`              | Generate webhook secret token                  |
| `webhook:set`                  | Register webhook with Telegram                 |
| `webhook:delete`               | Remove webhook from Telegram                   |
| `webhook:info`                 | Show current webhook info                      |
| `webhook:drop-pending`         | Clear pending Telegram updates                 |
