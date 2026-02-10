# راهنمای ابزار خط فرمان Sky Telegram Bot SDK

فایل `helper.php` یک ابزار خط فرمان برای مدیریت پروژه ربات تلگرام Sky Telegram Bot SDK است. این ابزار شامل مدیریت مایگریشن‌های دیتابیس، تولید کد، مدیریت وبهوک و راه‌اندازی اولیه پروژه می‌باشد.

## نحوه استفاده

```bash
php helper.php <دستور> [آرگومان‌ها] [فلگ‌ها]
```

اجرای `php helper.php` بدون آرگومان، لیست تمام دستورات موجود را نمایش می‌دهد.

---

## فهرست مطالب

- [راه‌اندازی اولیه](#راه‌اندازی-اولیه)
- [مایگریشن‌ها](#مایگریشن‌ها)
- [تولید کد](#تولید-کد)
- [مدیریت وبهوک](#مدیریت-وبهوک)
- [توکن امنیتی](#توکن-امنیتی)
- [شروع سریع](#شروع-سریع)

---

## راه‌اندازی اولیه

### `setup`

پروژه را برای اولین بار راه‌اندازی می‌کند. فایل `.env.example` را به `.env` کپی کرده و یک توکن امنیتی وبهوک تولید می‌کند.

```bash
php helper.php setup
```

**خروجی:**
```
  ✓ .env.example copied to .env
  ✓ Secret token generated and saved to .env
  Token: a3f9c8...
```

> **توجه:** اگر فایل `.env` از قبل وجود داشته باشد، این دستور با خطا مواجه می‌شود. ابتدا آن را حذف کنید.

پس از اجرا، فایل `.env` را باز کرده و مقادیر زیر را وارد کنید:

| متغیر                  | توضیحات                                      | مثال                                 |
|------------------------|----------------------------------------------|--------------------------------------|
| `DB_DRIVER`            | درایور دیتابیس (`sqlite` یا `mysql`)         | `sqlite`                             |
| `TELEGRAM_BOT_TOKEN`   | توکن ربات از @BotFather                      | `123456:ABC-DEF...`                  |
| `TELEGRAM_DOMAIN`      | دامنه سرور شما (با https)                    | `https://example.com`                |
| `TELEGRAM_ADMIN_ID`    | شناسه کاربری تلگرام شما                      | `123456789`                          |

---

## مایگریشن‌ها

فایل‌های مایگریشن در پوشه `database/migrations/` قرار دارند و به صورت ترتیبی شماره‌گذاری شده‌اند (مثلاً `001_create_users_table.php`).

### `migrate`

اجرای تمام مایگریشن‌های در انتظار (ساخت جداول).

```bash
php helper.php migrate
```

**خروجی:**
```
  ✓ Migrated: 001_create_users_table.php
  ✓ Migrated: 002_create_sessions_table.php
  All migrations completed.
```

### `migrate:down`

بازگردانی (rollback) تمام مایگریشن‌ها (حذف جداول به ترتیب معکوس).

```bash
php helper.php migrate:down
```

### `migrate:fresh`

حذف تمام جداول و اجرای مجدد تمام مایگریشن‌ها از ابتدا. مناسب برای محیط توسعه.

```bash
php helper.php migrate:fresh
```

---

## تولید کد

### `make:model <نام>`

ساخت یک مدل Eloquent جدید در پوشه `database/Models/`.

```bash
php helper.php make:model Post
```

فایل `database/Models/Post.php` ساخته می‌شود:

```php
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [];
}
```

### `make:model <نام> -m`

ساخت مدل **به همراه** فایل مایگریشن مربوطه.

```bash
php helper.php make:model Comment -m
```

فایل‌های زیر ساخته می‌شوند:
- `database/Models/Comment.php`
- `database/migrations/002_create_comments_table.php` (شماره به صورت خودکار محاسبه می‌شود)

### `make:migration <نام>`

ساخت یک فایل مایگریشن مستقل. نام باید توصیفی و به صورت snake_case باشد.

```bash
php helper.php make:migration create_posts_table
```

فایل `database/migrations/002_create_posts_table.php` ساخته می‌شود:

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

> شماره ترتیب بر اساس فایل‌های مایگریشن موجود به صورت خودکار تولید می‌شود.

---

## مدیریت وبهوک

این دستورات اتصال وبهوک تلگرام ربات شما را مدیریت می‌کنند. از متغیرهای `TELEGRAM_BOT_TOKEN`، `TELEGRAM_DOMAIN` و `TELEGRAM_WEBHOOK_SECRET` در فایل `.env` استفاده می‌شود.

### `webhook:set`

ثبت آدرس وبهوک در تلگرام. اگر توکن امنیتی وجود نداشته باشد، به صورت خودکار ساخته می‌شود.

```bash
php helper.php webhook:set
```

**خروجی:**
```
  ✓ Generated new secret token and saved to .env
  ✓ Set Webhook: Success
    Webhook was set
```

آدرس وبهوک به صورت خودکار ساخته می‌شود: `{TELEGRAM_DOMAIN}/webhook/{TELEGRAM_BOT_TOKEN}`

### `webhook:info`

نمایش اطلاعات وبهوک فعلی از تلگرام.

```bash
php helper.php webhook:info
```

**خروجی:**
```
Webhook Info:
  URL: https://example.com/webhook/123456:ABC-DEF...
  Custom Certificate: No
  Pending Updates: 0
  Max Connections: 40
```

### `webhook:delete`

حذف وبهوک (متوقف کردن دریافت آپدیت‌ها).

```bash
php helper.php webhook:delete
```

### `webhook:drop-pending`

حذف تمام آپدیت‌های در انتظار که تلگرام برای ربات شما در صف قرار داده است.

```bash
php helper.php webhook:drop-pending
```

---

## توکن امنیتی

### `generate:secret`

تولید یک توکن امنیتی ۶۴ کاراکتری هگزادسیمال و ذخیره آن در `TELEGRAM_WEBHOOK_SECRET` در فایل `.env`. این توکن برای تأیید اینکه درخواست‌های وبهوک واقعاً از طرف تلگرام هستند استفاده می‌شود.

```bash
php helper.php generate:secret
```

**خروجی:**
```
  ✓ Secret token generated and saved to .env
  Token: b7e4a1d9c3f8...
```

---

## شروع سریع

```bash
# ۱. نصب وابستگی‌ها
composer install

# ۲. راه‌اندازی اولیه پروژه
php helper.php setup

# ۳. ویرایش فایل .env با توکن ربات و دامنه
#    TELEGRAM_BOT_TOKEN=توکن_ربات_شما
#    TELEGRAM_DOMAIN=https://yourdomain.com

# ۴. اجرای مایگریشن‌ها
php helper.php migrate

# ۵. ثبت وبهوک
php helper.php webhook:set

# ۶. ساخت اولین مدل
php helper.php make:model Session -m

# ۷. اجرای مایگریشن جدید
php helper.php migrate
```

اکنون ربات شما آماده دریافت آپدیت‌ها در آدرس `https://yourdomain.com/webhook/{توکن_ربات}` می‌باشد.

---

## جدول مرجع دستورات

| دستور                          | توضیحات                                        |
|--------------------------------|------------------------------------------------|
| `setup`                        | کپی `.env.example` به `.env` و تولید توکن      |
| `migrate`                      | اجرای تمام مایگریشن‌های در انتظار               |
| `migrate:down`                 | بازگردانی تمام مایگریشن‌ها                      |
| `migrate:fresh`                | حذف همه و اجرای مجدد مایگریشن‌ها                |
| `make:model <نام>`             | ساخت مدل Eloquent جدید                         |
| `make:model <نام> -m`          | ساخت مدل به همراه مایگریشن                     |
| `make:migration <نام>`         | ساخت فایل مایگریشن جدید                        |
| `generate:secret`              | تولید توکن امنیتی وبهوک                        |
| `webhook:set`                  | ثبت وبهوک در تلگرام                            |
| `webhook:delete`               | حذف وبهوک از تلگرام                            |
| `webhook:info`                 | نمایش اطلاعات وبهوک فعلی                       |
| `webhook:drop-pending`         | پاکسازی آپدیت‌های در انتظار تلگرام              |
