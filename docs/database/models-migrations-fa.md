# راهنمای مدل‌ها و مایگریشن‌ها

Sky Telegram Bot SDK از **Eloquent ORM** (لاراول) برای عملیات دیتابیس استفاده می‌کند. مدل‌ها کلاس‌های PHP هستند که جداول دیتابیس را نشان می‌دهند، و مایگریشن‌ها اسکریپت‌های نسخه‌بندی شده‌ای هستند که جداول را ایجاد یا تغییر می‌دهند.

---

## فهرست مطالب

- [پیکربندی دیتابیس](#پیکربندی-دیتابیس)
- [مایگریشن‌ها](#مایگریشن‌ها)
  - [ساخت مایگریشن](#ساخت-مایگریشن)
  - [ساختار مایگریشن](#ساختار-مایگریشن)
  - [اجرای مایگریشن‌ها](#اجرای-مایگریشن‌ها)
  - [برگرداندن تغییرات](#برگرداندن-تغییرات)
  - [مایگریشن از صفر](#مایگریشن-از-صفر)
  - [مرجع انواع ستون‌ها](#مرجع-انواع-ستون‌ها)
- [مدل‌ها](#مدل‌ها)
  - [ساخت مدل](#ساخت-مدل)
  - [ساختار مدل](#ساختار-مدل)
  - [Fillable و Guarded](#fillable-و-guarded)
  - [Casts (تبدیل نوع)](#casts)
  - [کوئری‌ها](#کوئری‌ها)
  - [ایجاد و ویرایش رکورد](#ایجاد-و-ویرایش-رکورد)
  - [حذف رکورد](#حذف-رکورد)
  - [روابط (Relations)](#روابط)
  - [متدهای سفارشی](#متدهای-سفارشی)
- [استفاده در دستورات](#استفاده-در-دستورات)
- [مثال کامل](#مثال-کامل)

---

## پیکربندی دیتابیس

تنظیمات دیتابیس در `config/database.php` و `.env` قرار دارند:

### `.env`

```env
DB_DRIVER=sqlite
DB_PATH=storage/database.sqlite

# یا برای MySQL:
# DB_DRIVER=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_NAME=sky_bot
# DB_USER=root
# DB_PASS=secret
```

### درایورهای پشتیبانی شده

| درایور   | توضیحات                          |
|----------|----------------------------------|
| `sqlite` | فایل‌محور، بدون نیاز به پیکربندی |
| `mysql`  | سرور MySQL/MariaDB کامل          |

Eloquent به صورت خودکار در `config/bootstrap.php` راه‌اندازی می‌شود — نیازی به تنظیم دستی نیست.

---

## مایگریشن‌ها

مایگریشن‌ها در پوشه `database/migrations/` قرار دارند و به ترتیب پیشوند عددی اجرا می‌شوند.

### ساخت مایگریشن

از ابزار CLI استفاده کنید:

```bash
php helper.php make:migration create_posts_table
php helper.php make:migration add_email_to_users_table
```

این دستور فایلی مانند `database/migrations/002_create_posts_table.php` ایجاد می‌کند.

**قواعد نام‌گذاری:**

| الگوی نام                           | عملکرد                          |
|-------------------------------------|----------------------------------|
| `create_posts_table`                | ساخت جدول `posts`               |
| `add_email_to_users_table`          | تغییر جدول `users`              |
| `remove_avatar_from_users_table`    | تغییر جدول `users`              |

نام جدول به صورت خودکار از نام مایگریشن استخراج می‌شود.

### ساختار مایگریشن

هر مایگریشن یک کلاس ناشناس با متدهای `up()` و `down()` است:

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

- **`up()`** — هنگام مایگریت اجرا می‌شود (ساخت/تغییر جداول)
- **`down()`** — هنگام برگشت اجرا می‌شود (معکوس کردن تغییرات)

### تغییر جدول موجود

برای اضافه یا حذف ستون از جدول موجود:

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

### اجرای مایگریشن‌ها

```bash
# اجرای همه مایگریشن‌های در انتظار
php helper.php migrate
```

خروجی:

```
  ✓ Migrated: 001_create_users_table.php
  ✓ Migrated: 002_create_posts_table.php

Ran 2 migration(s).
```

### برگرداندن تغییرات

```bash
# برگرداندن همه مایگریشن‌ها
php helper.php migrate:down
```

خروجی:

```
  ✗ Rolled back: 002_create_posts_table.php
  ✗ Rolled back: 001_create_users_table.php

Rolled back 2 migration(s).
```

### مایگریشن از صفر

حذف همه جداول و اجرای مجدد از ابتدا:

```bash
php helper.php migrate:fresh
```

> **هشدار:** این دستور تمام داده‌ها را پاک می‌کند. فقط در محیط توسعه استفاده کنید.

### مرجع انواع ستون‌ها

انواع متداول ستون‌ها در Blueprint:

| متد                                          | توضیحات                          |
|----------------------------------------------|----------------------------------|
| `$table->id()`                               | کلید اصلی خودافزایش              |
| `$table->bigInteger('col')`                  | عدد صحیح بزرگ                    |
| `$table->integer('col')`                     | عدد صحیح                         |
| `$table->tinyInteger('col')`                 | عدد صحیح کوچک (0-255)            |
| `$table->string('col')`                      | VARCHAR (255)                    |
| `$table->string('col', 100)`                 | VARCHAR با طول مشخص               |
| `$table->text('col')`                        | TEXT                             |
| `$table->longText('col')`                    | LONGTEXT                        |
| `$table->boolean('col')`                     | بولین                            |
| `$table->float('col')`                       | اعشاری                           |
| `$table->decimal('col', 8, 2)`               | اعشاری با دقت مشخص               |
| `$table->timestamp('col')`                   | تایم‌استمپ                        |
| `$table->timestamps()`                       | `created_at` + `updated_at`     |
| `$table->json('col')`                        | ستون JSON                       |
| `$table->enum('col', ['a', 'b'])`            | ENUM                             |

**اصلاح‌کننده‌های ستون:**

| اصلاح‌کننده              | توضیحات                          |
|--------------------------|----------------------------------|
| `->nullable()`           | اجازه مقدار NULL                 |
| `->default('value')`     | مقدار پیش‌فرض                    |
| `->unique()`             | ایندکس یکتا                     |
| `->index()`              | ایندکس                           |
| `->after('col')`         | قرار دادن بعد از ستون دیگر (MySQL)|
| `->unsigned()`           | عدد صحیح بدون علامت              |
| `->comment('text')`      | توضیح ستون                       |

---

## مدل‌ها

مدل‌ها در پوشه `database/Models/` قرار دارند و از کلاس `Model` الکوئنت ارث‌بری می‌کنند.

### ساخت مدل

از ابزار CLI استفاده کنید:

```bash
php helper.php make:model Post
```

این دستور `database/Models/Post.php` را ایجاد می‌کند:

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

نام جدول به صورت خودکار از نام کلاس ساخته می‌شود:
- `Post` → `posts`
- `UserProfile` → `user_profiles`
- `Category` → `categories`

### ساختار مدل

هر مدل نماینده یک جدول دیتابیس است. هر نمونه (instance) یک سطر را نشان می‌دهد.

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

### Fillable و Guarded

`$fillable` مشخص می‌کند کدام ستون‌ها قابل انتساب انبوه هستند (از طریق `create()` ،`update()` ،`fill()`):

```php
protected $fillable = ['title', 'body', 'is_published'];
```

به جای آن می‌توانید از `$guarded` برای مسدود کردن ستون‌های خاص استفاده کنید:

```php
protected $guarded = ['id']; // همه چیز به جز 'id' قابل انتساب است
```

> همیشه `$fillable` یا `$guarded` را تعریف کنید تا از آسیب‌پذیری انتساب انبوه جلوگیری شود.

### Casts

تبدیل خودکار مقادیر دیتابیس به انواع PHP:

```php
protected $casts = [
    'telegram_id'    => 'integer',
    'is_bot'         => 'boolean',
    'is_banned'      => 'boolean',
    'settings'       => 'array',       // JSON ↔ آرایه
    'last_active_at' => 'datetime',    // رشته ↔ Carbon
];
```

| نوع تبدیل    | توضیحات                               |
|-------------|---------------------------------------|
| `integer`   | PHP `int`                              |
| `boolean`   | PHP `bool`                             |
| `float`     | PHP `float`                            |
| `string`    | PHP `string`                           |
| `array`     | ستون JSON به آرایه PHP                 |
| `object`    | ستون JSON به شیء PHP                   |
| `datetime`  | تایم‌استمپ به نمونه Carbon              |
| `date`      | رشته تاریخ به Carbon (فقط تاریخ)       |
| `timestamp` | تایم‌استمپ یونیکس                       |

### کوئری‌ها

```php
use Database\Models\User;

// پیدا کردن با کلید اصلی
$user = User::find(1);

// پیدا کردن یا خطا
$user = User::findOrFail(1);

// شرط Where
$admins = User::where('is_banned', false)->get();
$user   = User::where('telegram_id', 123456)->first();

// چند شرط
$users = User::where('is_banned', false)
    ->where('language_code', 'en')
    ->get();

// شمارش
$total = User::count();
$banned = User::where('is_banned', true)->count();

// مرتب‌سازی و محدودیت
$latest = User::orderByDesc('created_at')->take(10)->get();

// استخراج یک ستون
$names = User::pluck('first_name');

// بررسی وجود
$exists = User::where('telegram_id', 123456)->exists();
```

### ایجاد و ویرایش رکورد

```php
// ایجاد
$post = Post::create([
    'user_id' => 123,
    'title'   => 'سلام دنیا',
    'body'    => 'محتوا اینجاست',
]);

// ویرایش
$post->update(['title' => 'عنوان جدید']);

// خلاصه‌نویسی
$post->title = 'عنوان جدید';
$post->save();

// ایجاد یا ویرایش (upsert)
$user = User::updateOrCreate(
    ['telegram_id' => $from->id],          // معیار جستجو
    ['first_name'  => $from->first_name]   // مقادیر جدید
);

// اولی یا ایجاد
$user = User::firstOrCreate(
    ['telegram_id' => 123456],
    ['first_name' => 'ناشناس']
);
```

### حذف رکورد

```php
// حذف یک رکورد
$post->delete();

// حذف با کوئری
Post::where('is_published', false)->delete();

// حذف با شناسه
Post::destroy(1);
Post::destroy([1, 2, 3]);
```

### روابط

تعریف ارتباط بین مدل‌ها:

```php
class User extends Model
{
    // هر کاربر چندین پست دارد
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'telegram_id');
    }
}

class Post extends Model
{
    // هر پست متعلق به یک کاربر است
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'telegram_id');
    }
}
```

**نحوه استفاده:**

```php
// گرفتن پست‌های کاربر
$posts = $user->posts;

// گرفتن نویسنده پست
$author = $post->user;

// بارگذاری اشتیاقی (جلوگیری از مشکل N+1)
$posts = Post::with('user')->get();
```

**انواع روابط متداول:**

| متد                | توضیحات                  |
|--------------------|--------------------------|
| `hasOne()`         | یک‌به‌یک                  |
| `hasMany()`        | یک‌به‌چند                  |
| `belongsTo()`      | معکوس یک‌به‌یک/چند       |
| `belongsToMany()`  | چند‌به‌چند (جدول واسط)    |

### متدهای سفارشی

متدهای مخصوص دامنه کاری خود را به مدل اضافه کنید:

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

## استفاده در دستورات

مدل‌ها را ایمپورت کرده و مستقیماً در متد `handle()` دستور استفاده کنید:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\User;

class StatsCommand extends Command
{
    protected string $name = 'stats';
    protected string $description = 'نمایش آمار ربات';

    public function handle(): void
    {
        $total  = User::count();
        $active = User::where('last_active_at', '>=', now()->subDay())->count();
        $banned = User::where('is_banned', true)->count();

        $this->replyWithMessage([
            'text' => "📊 <b>آمار ربات</b>\n\n"
                    . "👥 کل کاربران: {$total}\n"
                    . "✅ فعال (۲۴ ساعت): {$active}\n"
                    . "🚫 مسدود شده: {$banned}",
        ]);
    }
}
```

همچنین می‌توانید از هلپر `db()` برای کوئری‌های خام استفاده کنید:

```php
use Illuminate\Database\Capsule\Manager as Capsule;

// کوئری خام
$users = Capsule::select('SELECT * FROM users WHERE is_banned = ?', [false]);

// کوئری بیلدر (بدون مدل)
$count = Capsule::table('users')->where('is_banned', false)->count();
```

---

## مثال کامل

یک گردش کار کامل — مایگریشن، مدل و دستور با هم.

### ۱. ساخت مایگریشن

```bash
php helper.php make:migration create_notes_table
```

ویرایش `database/migrations/002_create_notes_table.php`:

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

### ۲. اجرای مایگریشن

```bash
php helper.php migrate
```

### ۳. ساخت مدل

```bash
php helper.php make:model Note
```

ویرایش `database/Models/Note.php`:

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

### ۴. ساخت دستور

`Classes/Commands/Users/NoteCommand.php`:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\Note;

class NoteCommand extends Command
{
    protected string $name = 'note';
    protected string $description = 'مدیریت یادداشت‌ها';
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
            default => $this->reply("عملکرد نامشخص. استفاده: /note add <عنوان> یا /note list"),
        };
    }

    private function addNote(): void
    {
        $title = $this->argument('title');
        if (!$title) {
            $this->reply('استفاده: /note add <عنوان>');
            return;
        }

        Note::create([
            'telegram_id' => $this->getUserId(),
            'title'       => $title,
        ]);

        $this->reply("✅ یادداشت اضافه شد: {$title}");
    }

    private function listNotes(): void
    {
        $notes = Note::forUser($this->getUserId())->take(10)->get();

        if ($notes->isEmpty()) {
            $this->reply('📝 شما یادداشتی ندارید. با /note add <عنوان> یکی بسازید.');
            return;
        }

        $text = "📝 <b>یادداشت‌های شما</b>\n\n";
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
            $this->answerCallback('🗑 حذف شد');
        }

        if (str_starts_with($data, 'note_pin_')) {
            $id = (int) str_replace('note_pin_', '', $data);
            $note = Note::where('id', $id)
                ->where('telegram_id', $this->getUserId())
                ->first();
            if ($note) {
                $note->is_pinned ? $note->unpin() : $note->pin();
                $this->answerCallback($note->is_pinned ? '📌 سنجاق شد' : 'برداشته شد');
            }
        }

        $this->listNotes();
    }
}
```
