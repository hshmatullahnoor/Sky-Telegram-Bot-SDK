<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'telegram_id',
        'first_name',
        'last_name',
        'username',
        'language_code',
        'is_bot',
        'is_banned',
        'last_active_at',
    ];

    protected $casts = [
        'telegram_id'    => 'integer',
        'is_bot'         => 'boolean',
        'is_banned'      => 'boolean',
        'last_active_at' => 'datetime',
    ];

    /**
     * Find a user by their Telegram ID.
     */
    public static function findByTelegramId(int $telegramId): ?self
    {
        return self::where('telegram_id', $telegramId)->first();
    }

    /**
     * Create or update a user from a Telegram update.
     */
    public static function fromTelegram(object $from): self
    {
        return self::updateOrCreate(
            ['telegram_id' => $from->id],
            [
                'first_name'    => $from->first_name ?? '',
                'last_name'     => $from->last_name ?? null,
                'username'      => $from->username ?? null,
                'language_code' => $from->language_code ?? null,
                'is_bot'        => $from->is_bot ?? false,
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Check if user is banned.
     */
    public function banned(): bool
    {
        return $this->is_banned;
    }

    /**
     * Ban the user.
     */
    public function ban(): bool
    {
        return $this->update(['is_banned' => true]);
    }

    /**
     * Unban the user.
     */
    public function unban(): bool
    {
        return $this->update(['is_banned' => false]);
    }
}
