# Models & Migrations Guide

Sky Telegram Bot SDK uses **Eloquent ORM** (from Laravel) for database operations. Models are PHP classes that represent database tables, and migrations are versioned scripts that create or modify those tables.

---

## Table of Contents

- [Database Configuration](#database-configuration)
- [Migrations](#migrations)
  - [Creating a Migration](#creating-a-migration)
  - [Migration Structure](#migration-structure)
  - [Running Migrations](#running-migrations)
  - [Rolling Back](#rolling-back)
  - [Fresh Migration](#fresh-migration)
  - [Column Types Reference](#column-types-reference)
- [Models](#models)
  - [Creating a Model](#creating-a-model)
  - [Model Structure](#model-structure)
  - [Fillable & Guarded](#fillable--guarded)
  - [Casts](#casts)
  - [Querying](#querying)
  - [Creating & Updating Records](#creating--updating-records)
  - [Deleting Records](#deleting-records)
  - [Relationships](#relationships)
  - [Custom Methods](#custom-methods)
- [Using in Commands](#using-in-commands)
- [Full Example](#full-example)

---

## Database Configuration

Database settings are in `config/database.php` and `.env`:

### `.env`

```env
DB_DRIVER=sqlite
DB_PATH=storage/database.sqlite

# Or for MySQL:
# DB_DRIVER=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_NAME=sky_bot
# DB_USER=root
# DB_PASS=secret
```

### Supported Drivers

| Driver   | Description                    |
|----------|--------------------------------|
| `sqlite` | File-based, zero config        |
| `mysql`  | Full MySQL/MariaDB server      |

Eloquent is automatically booted in `config/bootstrap.php` — no manual setup needed.

---

## Migrations

Migrations live in `database/migrations/` and run in order by their numeric prefix.

### Creating a Migration

Use the CLI helper:

```bash
php helper.php make:migration create_posts_table
php helper.php make:migration add_email_to_users_table
```

This generates a file like `database/migrations/002_create_posts_table.php`.

**Naming conventions:**

| Name Pattern                      | Generated Action            |
|-----------------------------------|-----------------------------|
| `create_posts_table`              | Creates `posts` table       |
| `add_email_to_users_table`        | Modifies `users` table      |
| `remove_avatar_from_users_table`  | Modifies `users` table      |

The table name is auto-detected from the migration name.

### Migration Structure

Each migration is an anonymous class with `up()` and `down()` methods:

```php
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('title');
            $table->text('body');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('posts');
    }
};
```

- **`up()`** — runs when migrating (creates/modifies tables)
- **`down()`** — runs when rolling back (reverses changes)

### Modifying a Table

For adding or removing columns on an existing table:

```php
return new class
{
    public function up(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
```

### Running Migrations

```bash
# Run all pending migrations
php helper.php migrate

# Only shows status — won't re-run already migrated files
```

Output:

```
  ✓ Migrated: 001_create_users_table.php
  ✓ Migrated: 002_create_posts_table.php

Ran 2 migration(s).
```

### Rolling Back

```bash
# Rollback all migrations
php helper.php migrate:down
```

Output:

```
  ✗ Rolled back: 002_create_posts_table.php
  ✗ Rolled back: 001_create_users_table.php

Rolled back 2 migration(s).
```

### Fresh Migration

Drop everything and re-run from scratch:

```bash
php helper.php migrate:fresh
```

> **Warning:** This destroys all data. Use only in development.

### Column Types Reference

Common Blueprint column types:

| Method                                       | Description                       |
|----------------------------------------------|-----------------------------------|
| `$table->id()`                               | Auto-increment primary key        |
| `$table->bigInteger('col')`                  | Big integer                       |
| `$table->integer('col')`                     | Integer                           |
| `$table->tinyInteger('col')`                 | Tiny integer (0-255)              |
| `$table->string('col')`                      | VARCHAR (255)                     |
| `$table->string('col', 100)`                 | VARCHAR with length               |
| `$table->text('col')`                        | TEXT                              |
| `$table->longText('col')`                    | LONGTEXT                          |
| `$table->boolean('col')`                     | Boolean                           |
| `$table->float('col')`                       | Float                             |
| `$table->decimal('col', 8, 2)`               | Decimal with precision            |
| `$table->timestamp('col')`                   | Timestamp                         |
| `$table->timestamps()`                       | `created_at` + `updated_at`       |
| `$table->json('col')`                        | JSON column                       |
| `$table->enum('col', ['a', 'b'])`            | ENUM                              |

**Column modifiers:**

| Modifier                  | Description                       |
|---------------------------|-----------------------------------|
| `->nullable()`            | Allow NULL values                 |
| `->default('value')`      | Set default value                 |
| `->unique()`              | Add unique index                  |
| `->index()`               | Add index                         |
| `->after('col')`          | Place column after another (MySQL)|
| `->unsigned()`            | Unsigned integer                  |
| `->comment('text')`       | Column comment                    |

---

## Models

Models live in `database/Models/` and extend Eloquent's `Model` class.

### Creating a Model

Use the CLI helper:

```bash
php helper.php make:model Post
```

This creates `database/Models/Post.php`:

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        //
    ];

    protected $casts = [
        //
    ];
}
```

The table name is auto-generated from the class name:
- `Post` → `posts`
- `UserProfile` → `user_profiles`
- `Category` → `categories`

### Model Structure

A model maps to a database table. Each instance represents a row.

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'is_published',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'is_published' => 'boolean',
    ];
}
```

### Fillable & Guarded

`$fillable` defines which columns can be mass-assigned (via `create()`, `update()`, `fill()`):

```php
protected $fillable = ['title', 'body', 'is_published'];
```

Alternatively, use `$guarded` to block specific columns:

```php
protected $guarded = ['id']; // Everything except 'id' is fillable
```

> Always define `$fillable` or `$guarded` to prevent mass-assignment vulnerabilities.

### Casts

Automatically convert database values to PHP types:

```php
protected $casts = [
    'telegram_id'    => 'integer',
    'is_bot'         => 'boolean',
    'is_banned'      => 'boolean',
    'settings'       => 'array',       // JSON ↔ array
    'last_active_at' => 'datetime',    // String ↔ Carbon
];
```

| Cast Type    | Description                            |
|--------------|----------------------------------------|
| `integer`    | PHP `int`                              |
| `boolean`    | PHP `bool`                             |
| `float`      | PHP `float`                            |
| `string`     | PHP `string`                           |
| `array`      | JSON column to PHP array               |
| `object`     | JSON column to PHP object              |
| `datetime`   | Timestamp to Carbon instance           |
| `date`       | Date string to Carbon (date only)      |
| `timestamp`  | Unix timestamp                         |

### Querying

```php
use Database\Models\User;

// Find by primary key
$user = User::find(1);

// Find or fail (throws exception)
$user = User::findOrFail(1);

// Where clauses
$admins = User::where('is_banned', false)->get();
$user   = User::where('telegram_id', 123456)->first();

// Multiple conditions
$users = User::where('is_banned', false)
    ->where('language_code', 'en')
    ->get();

// Count
$total = User::count();
$banned = User::where('is_banned', true)->count();

// Order & limit
$latest = User::orderByDesc('created_at')->take(10)->get();

// Pluck single column
$names = User::pluck('first_name');

// Check existence
$exists = User::where('telegram_id', 123456)->exists();
```

### Creating & Updating Records

```php
// Create
$post = Post::create([
    'user_id' => 123,
    'title'   => 'Hello World',
    'body'    => 'Content here',
]);

// Update
$post->update(['title' => 'New Title']);

// Shorthand
$post->title = 'New Title';
$post->save();

// Create or update (upsert)
$user = User::updateOrCreate(
    ['telegram_id' => $from->id],          // Search criteria
    ['first_name'  => $from->first_name]   // Values to set
);

// First or create
$user = User::firstOrCreate(
    ['telegram_id' => 123456],
    ['first_name' => 'Unknown']
);
```

### Deleting Records

```php
// Delete a single record
$post->delete();

// Delete by query
Post::where('is_published', false)->delete();

// Delete by ID
Post::destroy(1);
Post::destroy([1, 2, 3]);
```

### Relationships

Define relationships between models:

```php
class User extends Model
{
    // One user has many posts
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'telegram_id');
    }
}

class Post extends Model
{
    // Each post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'telegram_id');
    }
}
```

**Usage:**

```php
// Get user's posts
$posts = $user->posts;

// Get post's author
$author = $post->user;

// Eager loading (avoid N+1 queries)
$posts = Post::with('user')->get();
```

**Common relationship types:**

| Method           | Description              |
|------------------|--------------------------|
| `hasOne()`       | One-to-one               |
| `hasMany()`      | One-to-many              |
| `belongsTo()`    | Inverse one-to-one/many  |
| `belongsToMany()`| Many-to-many (pivot)     |

### Custom Methods

Add domain-specific methods to your models:

```php
class User extends Model
{
    // ...

    public static function findByTelegramId(int $telegramId): ?self
    {
        return self::where('telegram_id', $telegramId)->first();
    }

    public static function fromTelegram(object $from): self
    {
        return self::updateOrCreate(
            ['telegram_id' => $from->id],
            [
                'first_name'     => $from->first_name ?? '',
                'last_name'      => $from->last_name ?? null,
                'username'       => $from->username ?? null,
                'language_code'  => $from->language_code ?? null,
                'is_bot'         => $from->is_bot ?? false,
                'last_active_at' => now(),
            ]
        );
    }

    public function ban(): bool
    {
        return $this->update(['is_banned' => true]);
    }

    public function unban(): bool
    {
        return $this->update(['is_banned' => false]);
    }

    public function banned(): bool
    {
        return $this->is_banned;
    }
}
```

---

## Using in Commands

Import models and use them directly in your command's `handle()` method:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\User;

class StatsCommand extends Command
{
    protected string $name = 'stats';
    protected string $description = 'Show bot statistics';

    public function handle(): void
    {
        $total  = User::count();
        $active = User::where('last_active_at', '>=', now()->subDay())->count();
        $banned = User::where('is_banned', true)->count();

        $this->replyWithMessage([
            'text' => "📊 <b>Bot Statistics</b>\n\n"
                    . "👥 Total users: {$total}\n"
                    . "✅ Active (24h): {$active}\n"
                    . "🚫 Banned: {$banned}",
        ]);
    }
}
```

You can also use the `db()` helper for raw queries:

```php
use Illuminate\Database\Capsule\Manager as Capsule;

// Raw query
$users = Capsule::select('SELECT * FROM users WHERE is_banned = ?', [false]);

// Query builder (without model)
$count = Capsule::table('users')->where('is_banned', false)->count();
```

---

## Full Example

A complete workflow — migration, model, and command together.

### 1. Create Migration

```bash
php helper.php make:migration create_notes_table
```

Edit `database/migrations/002_create_notes_table.php`:

```php
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index('telegram_id');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('notes');
    }
};
```

### 2. Run Migration

```bash
php helper.php migrate
```

### 3. Create Model

```bash
php helper.php make:model Note
```

Edit `database/Models/Note.php`:

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $table = 'notes';

    protected $fillable = [
        'telegram_id',
        'title',
        'content',
        'is_pinned',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'is_pinned'   => 'boolean',
    ];

    public static function forUser(int $telegramId)
    {
        return self::where('telegram_id', $telegramId)->orderByDesc('created_at');
    }

    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }
}
```

### 4. Create Command

`Classes/Commands/Users/NoteCommand.php`:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\Note;

class NoteCommand extends Command
{
    protected string $name = 'note';
    protected string $description = 'Manage your notes';
    protected string $pattern = '{action} {title}';
    protected array $callbackAliases = ['note_delete_*', 'note_pin_*'];

    public function handle(): void
    {
        if ($this->isCallbackQuery()) {
            $this->handleCallback();
            return;
        }

        $action = $this->argument('action', 'list');

        match ($action) {
            'add'  => $this->addNote(),
            'list' => $this->listNotes(),
            default => $this->reply("Unknown action. Use: /note add <title> or /note list"),
        };
    }

    private function addNote(): void
    {
        $title = $this->argument('title');
        if (!$title) {
            $this->reply('Usage: /note add <title>');
            return;
        }

        Note::create([
            'telegram_id' => $this->getUserId(),
            'title'       => $title,
        ]);

        $this->reply("✅ Note added: {$title}");
    }

    private function listNotes(): void
    {
        $notes = Note::forUser($this->getUserId())->take(10)->get();

        if ($notes->isEmpty()) {
            $this->reply('📝 You have no notes. Use /note add <title> to create one.');
            return;
        }

        $text = "📝 <b>Your Notes</b>\n\n";
        $keyboard = [];

        foreach ($notes as $note) {
            $pin = $note->is_pinned ? '📌 ' : '';
            $text .= "• {$pin}{$note->title}\n";
            $keyboard[] = [
                ['text' => "📌 {$note->title}", 'callback_data' => "note_pin_{$note->id}"],
                ['text' => "🗑", 'callback_data' => "note_delete_{$note->id}"],
            ];
        }

        $this->replyWithKeyboard($text, $keyboard);
    }

    private function handleCallback(): void
    {
        $data = $this->callbackData;

        if (str_starts_with($data, 'note_delete_')) {
            $id = (int) str_replace('note_delete_', '', $data);
            Note::where('id', $id)
                ->where('telegram_id', $this->getUserId())
                ->delete();
            $this->answerCallback('🗑 Deleted');
        }

        if (str_starts_with($data, 'note_pin_')) {
            $id = (int) str_replace('note_pin_', '', $data);
            $note = Note::where('id', $id)
                ->where('telegram_id', $this->getUserId())
                ->first();
            if ($note) {
                $note->is_pinned ? $note->unpin() : $note->pin();
                $this->answerCallback($note->is_pinned ? '📌 Pinned' : 'Unpinned');
            }
        }

        $this->listNotes();
    }
}
```
