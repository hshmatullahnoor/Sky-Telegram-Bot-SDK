<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('language_code', 10)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('users');
    }
};
