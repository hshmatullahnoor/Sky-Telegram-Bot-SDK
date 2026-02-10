<?php

namespace Classes\Commands\Helper\Make;

class MakeMigrationCommand
{
    private string $migrationsPath;

    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? base_path('database/migrations');
    }

    /**
     * Create a new migration file.
     *
     * Examples:
     *   php helper.php make:migration create_posts_table
     *   php helper.php make:migration add_email_to_users_table
     */
    public function handle(string $name): void
    {
        if (empty($name)) {
            echo "Usage: php helper.php make:migration <name>" . PHP_EOL;
            return;
        }

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $sequence = $this->getNextSequence();
        $filename = "{$sequence}_{$name}.php";
        $filePath = $this->migrationsPath . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filePath)) {
            echo "  ✗ Migration already exists: {$filename}" . PHP_EOL;
            return;
        }

        $table = $this->guessTableName($name);
        $isCreate = str_starts_with($name, 'create_');

        $stub = $isCreate
            ? $this->getCreateStub($table)
            : $this->getUpdateStub($table);

        file_put_contents($filePath, $stub);
        echo "  ✓ Migration created: database/migrations/{$filename}" . PHP_EOL;
    }

    private function getCreateStub(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('{$table}', function (Blueprint \$table) {
            \$table->id();
            //
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('{$table}');
    }
};

PHP;
    }

    private function getUpdateStub(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('{$table}', function (Blueprint \$table) {
            //
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('{$table}', function (Blueprint \$table) {
            //
        });
    }
};

PHP;
    }

    /**
     * Get the next migration sequence number (zero-padded).
     */
    private function getNextSequence(): string
    {
        $files = glob($this->migrationsPath . '/*.php');

        if (empty($files)) {
            return '001';
        }

        $last = basename(end($files));
        preg_match('/^(\d+)_/', $last, $matches);
        $next = isset($matches[1]) ? (int) $matches[1] + 1 : 1;

        return str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Guess table name from migration name.
     *
     * create_posts_table     → posts
     * add_email_to_users_table → users
     */
    private function guessTableName(string $name): string
    {
        // create_xxx_table
        if (preg_match('/^create_(.+)_table$/', $name, $m)) {
            return $m[1];
        }

        // add_xxx_to_yyy_table / remove_xxx_from_yyy_table
        if (preg_match('/(?:to|from)_(.+)_table$/', $name, $m)) {
            return $m[1];
        }

        // fallback: remove _table suffix if present
        return preg_replace('/_table$/', '', $name);
    }
}
