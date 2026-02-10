# راهنمای سیستم دستورات Sky Telegram Bot SDK

سیستم دستورات هسته اصلی ربات تلگرام شماست. دستورات کلاس‌های PHP هستند که آپدیت‌های ورودی — پیام‌ها، کوئری‌های کال‌بک، کوئری‌های اینلاین و غیره — را پردازش می‌کنند. آن‌ها به صورت **خودکار شناسایی** شده و نیازی به ثبت دستی ندارند.

---

## فهرست مطالب

- [نحوه کار](#نحوه-کار)
- [ساخت دستور جدید](#ساخت-دستور-جدید)
- [ویژگی‌های دستور](#ویژگی‌های-دستور)
- [آرگومان‌ها و الگوها](#آرگومان‌ها-و-الگوها)
- [نام‌های جایگزین و تطبیق](#نام‌های-جایگزین-و-تطبیق)
- [کوئری‌های کال‌بک](#کوئری‌های-کال‌بک)
- [کوئری‌های اینلاین](#کوئری‌های-اینلاین)
- [تریگرها (انواع آپدیت)](#تریگرها)
- [هلپرهای متنی](#هلپرهای-متنی)
- [متدهای پاسخ‌دهی](#متدهای-پاسخ‌دهی)
- [دسترسی مستقیم به SDK](#دسترسی-مستقیم-به-sdk)
- [ثبت خودکار](#ثبت-خودکار)
- [مثال کامل](#مثال-کامل)

---

## نحوه کار

```
تلگرام → POST /webhook/{token} → Router → CommandHandler → Command::handle()
```

1. تلگرام یک آپدیت به آدرس وبهوک شما ارسال می‌کند
2. `CommandHandler` آپدیت را دریافت می‌کند
3. دستورات ثبت‌شده را بررسی می‌کند
4. اولین دستوری که با آپدیت **مطابقت** دارد اجرا می‌شود
5. متد `handle()` دستور، منطق شما را اجرا می‌کند

---

## ساخت دستور جدید

یک فایل PHP در پوشه `Classes/Commands/Users/` بسازید — به صورت خودکار ثبت می‌شود.

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;

class PingCommand extends Command
{
    protected string $name = 'ping';
    protected string $description = 'بررسی وضعیت ربات';

    public function handle(): void
    {
        $this->replyWithMessage([
            'text' => '🏓 Pong!'
        ]);
    }
}
```

همین! پیام `/ping` را به ربات بفرستید و پاسخ "🏓 Pong!" برمی‌گرداند.

---

## ویژگی‌های دستور

| ویژگی              | نوع      | پیش‌فرض                          | توضیحات                                                |
|--------------------|----------|----------------------------------|--------------------------------------------------------|
| `$name`            | `string` | `''`                             | نام دستور بدون `/` (مثلاً `start`)                     |
| `$description`     | `string` | `''`                             | نمایش در `/help` و BotFather                           |
| `$pattern`         | `string` | `''`                             | الگوی آرگومان (مثلاً `{username} {age: \d+}`)          |
| `$aliases`         | `array`  | `[]`                             | پیام‌های متنی که این دستور را فعال می‌کنند              |
| `$callbackAliases` | `array`  | `[]`                             | الگوهای کال‌بک (از `*` پشتیبانی می‌کند)                |
| `$inlineAliases`   | `array`  | `[]`                             | الگوهای اینلاین (از `*` پشتیبانی می‌کند)               |
| `$triggers`        | `array`  | `['message', 'callback_query']`  | انواع آپدیت‌هایی که دستور به آن‌ها گوش می‌دهد          |

---

## آرگومان‌ها و الگوها

### بدون الگو (موقعیتی)

اگر `$pattern` تعریف نشود، آرگومان‌ها به صورت آرایه ایندکس‌دار ذخیره می‌شوند:

```php
class GreetCommand extends Command
{
    protected string $name = 'greet';

    public function handle(): void
    {
        $name = $this->argument(0, 'دوست');
        $this->replyWithMessage(['text' => "سلام، {$name}!"]);
    }
}
```

`/greet علی` → `سلام، علی!`
`/greet` → `سلام، دوست!`

### با الگوی نام‌گذاری شده

با تعریف `$pattern` آرگومان‌ها را نام‌گذاری کنید:

```php
class GreetCommand extends Command
{
    protected string $name = 'greet';
    protected string $pattern = '{username}';

    public function handle(): void
    {
        $username = $this->argument('username', 'دوست');
        $this->replyWithMessage(['text' => "سلام، {$username}!"]);
    }
}
```

`/greet علی` → `سلام، علی!`

### چند آرگومان با اعتبارسنجی

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
            $this->replyWithMessage(['text' => 'لطفاً نام کاربری خود را وارد کنید. مثال: /register ali 25']);
            return;
        }

        if (!$age) {
            $this->replyWithMessage(['text' => 'لطفاً سن خود را وارد کنید. مثال: /register ali 25']);
            return;
        }

        $this->replyWithMessage(['text' => "خوش آمدید {$username}، سن: {$age}!"]);
    }
}
```

`/register ali 25` → `خوش آمدید ali، سن: 25!`
`/register ali abc` → سن `null` می‌شود (اعتبارسنجی `\d+` رد شد)

### ساختار الگو

```
{name}              — هر کلمه‌ای را می‌گیرد
{name: regex}       — می‌گیرد و با regex اعتبارسنجی می‌کند
```

**مثال‌ها:**
```php
'{username}'                     // هر کلمه
'{username} {age: \d+}'         // کلمه + فقط اعداد
'{action: start|stop|restart}'  // فقط این مقادیر
'{id: [0-9a-f]{8}}'            // رشته هگزادسیمال، ۸ کاراکتر
```

### متد `argument()`

```php
$this->argument()                    // همه آرگومان‌ها به صورت آرایه
$this->argument('username')          // آرگومان نام‌گذاری شده (null اگر نباشد)
$this->argument('username', 'مهمان') // آرگومان با مقدار پیش‌فرض
$this->argument(0)                   // آرگومان اول با ایندکس
$this->argument(0, 'پیش‌فرض')        // آرگومان اول با مقدار پیش‌فرض
```

متدهای دیگر:
```php
$this->argumentString()    // همه آرگومان‌ها به صورت یک رشته
$this->hasArguments()      // آیا آرگومانی وجود دارد؟
```

---

## نام‌های جایگزین و تطبیق

### نام‌های جایگزین متنی

فعال‌سازی دستور از طریق پیام‌های متنی ساده (بدون حساسیت به بزرگی/کوچکی حروف):

```php
class StartCommand extends Command
{
    protected string $name = 'start';
    protected array $aliases = ['🏠 Home', 'menu', 'شروع'];
}
```

همه این‌ها دستور start را فعال می‌کنند:
- `/start`
- `🏠 Home`
- `MENU`
- `شروع`

### نام‌های جایگزین کال‌بک (با کاراکتر عام)

تطبیق با داده‌های کال‌بک دکمه‌های کیبورد اینلاین:

```php
class SettingsCommand extends Command
{
    protected string $name = 'settings';
    protected array $callbackAliases = ['settings', 'setting_*'];
}
```

مطابقت دارد با: `settings`، `setting_theme`، `setting_language` و غیره.

داده کال‌بک مطابقت‌یافته از طریق `$this->callbackData` در دسترس است.

---

## کوئری‌های کال‌بک

پردازش فشردن دکمه‌های کیبورد اینلاین:

```php
class ConfirmCommand extends Command
{
    protected string $name = '';
    protected array $callbackAliases = ['confirm_*', 'cancel_*'];
    protected array $triggers = ['callback_query'];

    public function handle(): void
    {
        $data = $this->callbackData;

        // همیشه کال‌بک را پاسخ دهید تا نشانگر بارگذاری حذف شود
        $this->answerCallback('انجام شد!');

        if (str_starts_with($data, 'confirm_')) {
            $id = str_replace('confirm_', '', $data);
            $this->editMessage("✅ تأیید شد #{$id}");
        } else {
            $this->editMessage("❌ لغو شد");
        }
    }
}
```

### پارامترهای `answerCallback()`

```php
$this->answerCallback();                             // بستن بی‌صدا
$this->answerCallback('ذخیره شد!');                   // اعلان کوتاه
$this->answerCallback('آیا مطمئن هستید؟', true);      // پنجره هشدار
```

---

## کوئری‌های اینلاین

پردازش جستجوهای `@yourbot <query>`:

```php
class InlineSearchCommand extends Command
{
    protected string $name = '';
    protected array $inlineAliases = ['*'];
    protected array $triggers = ['inline_query'];

    public function handle(): void
    {
        $query = $this->inlineQuery;

        $this->telegram->answerInlineQuery([
            'inline_query_id' => $this->update->inlineQuery->id,
            'results' => json_encode([
                [
                    'type' => 'article',
                    'id' => '1',
                    'title' => "نتیجه برای: {$query}",
                    'input_message_content' => [
                        'message_text' => "شما جستجو کردید: {$query}",
                    ],
                ],
            ]),
        ]);
    }
}
```

---

## تریگرها

به صورت پیش‌فرض، دستورات به `message` و `callback_query` گوش می‌دهند. `$triggers` را بازنویسی کنید:

```php
protected array $triggers = ['message', 'callback_query'];
```

**تریگرهای موجود:**

| تریگر                 | توضیحات                          |
|-----------------------|----------------------------------|
| `message`             | پیام متنی/رسانه‌ای جدید          |
| `callback_query`      | فشردن دکمه کیبورد اینلاین       |
| `inline_query`        | کوئری اینلاین                    |
| `edited_message`      | ویرایش پیام توسط کاربر          |
| `channel_post`        | پست جدید در کانال                |
| `edited_channel_post` | ویرایش پست کانال                 |
| `chat_member`         | تغییر وضعیت عضو چت              |
| `my_chat_member`      | تغییر عضویت خود ربات             |
| `chat_join_request`   | درخواست عضویت کاربر              |
| `pre_checkout_query`  | پیش‌پرداخت                       |
| `shipping_query`      | کوئری ارسال                      |
| `poll`                | تغییر وضعیت نظرسنجی             |
| `poll_answer`         | پاسخ کاربر به نظرسنجی           |

---

## هلپرهای متنی

دسترسی به اطلاعات آپدیت فعلی:

```php
// کاربر و چت
$this->getFrom()           // آبجکت کاربر
$this->getUserId()         // آی‌دی تلگرام کاربر
$this->getChat()           // آبجکت چت
$this->getChatId()         // آی‌دی چت

// پیام
$this->getMessage()        // آبجکت پیام
$this->getMessageText()    // متن پیام
$this->getMessageId()      // آی‌دی پیام

// آپدیت
$this->getUpdate()         // آبجکت کامل آپدیت
$this->isCallbackQuery()   // آیا کوئری کال‌بک است؟
$this->isInlineQuery()     // آیا کوئری اینلاین است؟

// داده‌های مطابقت‌یافته
$this->callbackData        // رشته داده کال‌بک
$this->inlineQuery         // رشته کوئری اینلاین
```

---

## متدهای پاسخ‌دهی

همه متدها `chat_id` و `parse_mode` را از کانفیگ پر می‌کنند.

### پیام‌های متنی

```php
// پاسخ ساده
$this->reply('سلام!');

// پاسخ با گزینه‌های اضافی
$this->reply('سلام!', ['disable_notification' => true]);

// سبک SDK (پارامترهای sendMessage مستقیم)
$this->replyWithMessage([
    'text' => 'سلام!',
    'reply_markup' => json_encode([
        'inline_keyboard' => [
            [['text' => 'کلیک کنید', 'callback_data' => 'clicked']]
        ]
    ])
]);
```

### کیبورد اینلاین

```php
$this->replyWithKeyboard('یک گزینه انتخاب کنید:', [
    [
        ['text' => '✅ بله', 'callback_data' => 'yes'],
        ['text' => '❌ خیر', 'callback_data' => 'no'],
    ],
    [
        ['text' => '🔙 بازگشت', 'callback_data' => 'back'],
    ],
]);
```

### ویرایش پیام

```php
// ویرایش متن
$this->editMessage('متن جدید!');

// ویرایش متن با کیبورد جدید
$this->editMessage('به‌روز شد!', [
    'reply_markup' => json_encode([
        'inline_keyboard' => [
            [['text' => 'دکمه جدید', 'callback_data' => 'new']]
        ]
    ])
]);

// ویرایش فقط کیبورد
$this->editKeyboard([
    [['text' => 'تغییر یافت', 'callback_data' => 'changed']]
]);
```

### رسانه

```php
// عکس
$this->replyWithPhoto('https://example.com/photo.jpg', 'توضیح');

// سند
$this->replyWithDocument('/path/to/file.pdf', 'فایل شما');

// ویدیو
$this->replyWithVideo('https://example.com/video.mp4', 'توضیح ویدیو');

// صدا
$this->replyWithVoice('https://example.com/voice.ogg');

// استیکر
$this->replyWithSticker('sticker_file_id');

// موقعیت مکانی
$this->replyWithLocation([
    'latitude' => 35.6892,
    'longitude' => 51.3890,
]);

// مخاطب
$this->replyWithContact([
    'phone_number' => '+989123456789',
    'first_name' => 'علی',
]);
```

### عملیات و سایر

```php
// ارسال نشانگر "در حال تایپ..."
$this->sendAction('typing');

// حذف پیام
$this->deleteMessage();

// فوروارد پیام
$this->forwardTo($chatId);

// پاسخ کال‌بک
$this->answerCallback();                      // بی‌صدا
$this->answerCallback('ذخیره شد!');            // اعلان
$this->answerCallback('مطمئنید؟', true);       // هشدار
```

---

## دسترسی مستقیم به SDK

برای هر متد API تلگرام که در میانبرها نیست، مستقیماً از SDK استفاده کنید:

```php
public function handle(): void
{
    $telegram = $this->getTelegram();

    $telegram->sendPoll([
        'chat_id' => $this->getChatId(),
        'question' => 'رنگ مورد علاقه؟',
        'options' => ['قرمز', 'آبی', 'سبز'],
    ]);
}
```

---

## ثبت خودکار

دستورات به صورت خودکار از پوشه `Classes/Commands/Users/` شناسایی می‌شوند. هر کلاس PHP در این پوشه که:

1. از `Classes\Commands\Command` ارث‌بری کند
2. **abstract** نباشد

...به صورت خودکار ثبت می‌شود. نیازی به کانفیگ نیست.

پوشه اسکن در `config/bot.php` تنظیم شده:

```php
'commands_path' => base_path('Classes/Commands/Users'),
```

### ترتیب اجرا

دستورات به ترتیب کشف (الفبایی بر اساس نام فایل) بررسی می‌شوند. **اولین تطابق برنده** است و بقیه دستورات بررسی نمی‌شوند.

---

## مثال کامل

یک دستور کامل که `/profile` را مدیریت می‌کند، شامل دکمه‌های کال‌بک و آرگومان‌های نام‌گذاری شده:

```php
<?php

namespace Classes\Commands\Users;

use Classes\Commands\Command;
use Database\Models\User;

class ProfileCommand extends Command
{
    protected string $name = 'profile';
    protected string $description = 'مشاهده پروفایل';
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
        $targetId = $this->argument('user_id', $this->getUserId());

        $user = User::findByTelegramId($targetId);
        if (!$user) {
            $this->replyWithMessage(['text' => '❌ کاربر یافت نشد.']);
            return;
        }

        $text = "👤 <b>پروفایل</b>\n\n"
              . "نام: {$user->first_name}\n"
              . "نام کاربری: @{$user->username}\n"
              . "تاریخ عضویت: {$user->created_at->format('Y/m/d')}";

        $keyboard = [
            [['text' => '✏️ ویرایش', 'callback_data' => 'profile_edit']],
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
        $this->editMessage("✏️ <b>ویرایش پروفایل</b>\n\nنام جدید خود را ارسال کنید:", [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🔙 بازگشت', 'callback_data' => 'profile_back']],
                ],
            ]),
        ]);
    }
}
```
