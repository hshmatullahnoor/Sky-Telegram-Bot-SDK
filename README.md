<div align="center">

# Sky Telegram Bot SDK

A lightweight, Laravel-inspired PHP framework for building Telegram bots.

**[English](#-about)** · **[فارسی](#-درباره-پروژه)**

</div>

---

## 🇬🇧 About

Sky Telegram Bot SDK is a PHP Telegram bot framework built on top of [Telegram Bot SDK](https://github.com/irazasyed/telegram-bot-sdk). It provides a clean, organized structure with **auto-discovered commands**, **Eloquent ORM**, **pattern-based arguments**, and a **routing system** — so you can focus on building your bot, not the boilerplate.

### Features

- **Auto-registered commands** — drop a class in `Commands/Users/`, it just works
- **Pattern-based arguments** — named args with regex validation (`{age: \d+}`)
- **Eloquent ORM** — full database support with models & migrations
- **Routing system** — lightweight router with `.htaccess` support
- **CLI helper tool** — generate models, migrations, manage webhooks
- **Callback & Inline query support** — wildcard matching with `*`
- **Built-in logging** — file, daily rotation, stderr, stdout channels

### Requirements

- PHP 8.1+
- Composer
- SQLite or MySQL

### Quick Start

```bash
# Clone the project
git clone https://github.com/your-repo/sky-telegram-bot-sdk.git
cd sky-telegram-bot-sdk

# Install dependencies
composer install

# Setup environment
cp .env.example .env
# Edit .env with your bot token and database settings

# Run migrations
php helper.php migrate

# Set webhook
php helper.php webhook:set
```

### Project Structure

```
├── Classes/
│   ├── Commands/
│   │   ├── Command.php          # Base command class
│   │   ├── CommandHandler.php   # Auto-discovery & dispatch
│   │   └── Users/               # Your bot commands (auto-registered)
│   ├── Router.php               # HTTP router
│   └── Log.php                  # Logger
├── config/
│   ├── bootstrap.php            # App bootstrap & helpers
│   ├── bot.php                  # Bot configuration
│   └── database.php             # Database configuration
├── database/
│   ├── migrations/              # Migration files
│   └── Models/                  # Eloquent models
├── docs/                        # Documentation
├── public/
│   ├── index.php                # Front controller
│   └── .htaccess                # Apache rewrite rules
├── routes/
│   └── web.php                  # Route definitions
├── storage/                     # Logs & SQLite database
├── helper.php                   # CLI tool
└── .env                         # Environment variables
```

### 📖 Documentation

| Topic                  | Link                                                       |
|------------------------|------------------------------------------------------------|
| Commands System        | [docs/commands/commands.md](docs/commands/commands.md)      |
| Models & Migrations    | [docs/database/models-migrations.md](docs/database/models-migrations.md) |
| CLI Helper Tool        | [docs/helper/helper.md](docs/helper/helper.md)             |

---

## 🇮🇷 درباره پروژه

Sky Telegram Bot SDK یک فریمورک PHP برای ساخت ربات تلگرام است که بر پایه [Telegram Bot SDK](https://github.com/irazasyed/telegram-bot-sdk) ساخته شده. ساختاری تمیز و منظم با **شناسایی خودکار دستورات**، **Eloquent ORM**، **آرگومان‌های الگو‌محور** و **سیستم مسیریابی** ارائه می‌دهد — تا شما روی ساخت ربات تمرکز کنید، نه کدهای تکراری.

### ویژگی‌ها

- **ثبت خودکار دستورات** — کلاس را در `Commands/Users/` بگذارید، خودش کار می‌کند
- **آرگومان‌های الگو‌محور** — آرگومان‌های نام‌دار با اعتبارسنجی regex (`{age: \d+}`)
- **Eloquent ORM** — پشتیبانی کامل دیتابیس با مدل‌ها و مایگریشن‌ها
- **سیستم مسیریابی** — روتر سبک با پشتیبانی `.htaccess`
- **ابزار CLI** — ساخت مدل، مایگریشن، مدیریت وبهوک
- **پشتیبانی کال‌بک و اینلاین** — تطبیق با کاراکتر عام `*`
- **لاگ‌گیری داخلی** — کانال‌های فایل، روزانه، stderr، stdout

### پیش‌نیازها

- PHP 8.1+
- Composer
- SQLite یا MySQL

### شروع سریع

```bash
# کلون پروژه
git clone https://github.com/your-repo/sky-telegram-bot-sdk.git
cd sky-telegram-bot-sdk

# نصب وابستگی‌ها
composer install

# راه‌اندازی محیط
cp .env.example .env
# فایل .env را با توکن ربات و تنظیمات دیتابیس ویرایش کنید

# اجرای مایگریشن‌ها
php helper.php migrate

# تنظیم وبهوک
php helper.php webhook:set
```

### 📖 مستندات

| موضوع                   | لینک                                                                    |
|-------------------------|-------------------------------------------------------------------------|
| سیستم دستورات           | [docs/commands/commands-fa.md](docs/commands/commands-fa.md)             |
| مدل‌ها و مایگریشن‌ها     | [docs/database/models-migrations-fa.md](docs/database/models-migrations-fa.md) |
| ابزار CLI               | [docs/helper/helper-fa.md](docs/helper/helper-fa.md)                    |

---

<div align="center">

Made with ❤️ by [Hshmat Ullah Noor](mailto:hshmatullahnoor@gmail.com)

</div>
