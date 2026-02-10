<?php

namespace Classes\Commands\Helper;

use Illuminate\Database\Capsule\Manager as Capsule;

class MigrateCommand
{
    private string $migrationsPath;

    public function __construct(?string $migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? base_path('database/migrations');
        $this->ensureMigrationsTable();
    }

    /**
     * Run from CLI arguments.
     *
     * Usage:
     *   php migrate.php          Run pending migrations
     *   php migrate.php --down   Rollback all
     *   php migrate.php --fresh  Drop & re-run
     */
    public function handle(array $argv = []): void
    {
        $action = $argv[1] ?? '--up';

        match ($action) {
            '--up'    => $this->up(),
            '--down'  => $this->down(),
            '--fresh' => $this->fresh(),
            default   => print("Unknown action: {$action}" . PHP_EOL . "Usage: php migrate.php [--up|--down|--fresh]" . PHP_EOL),
        };
    }

    /**
     * Run all pending migrations.
     */
    public function up(): void
    {
        $files = $this->getMigrationFiles();

        if (empty($files)) {
            echo "No migration files found." . PHP_EOL;
            return;
        }

        $ran = Capsule::table('migrations')->pluck('migration')->toArray();
        $batch = (int) Capsule::table('migrations')->max('batch') + 1;
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $ran)) {
                continue;
            }

            $migration = require $file;
            $migration->up();

            Capsule::table('migrations')->insert([
                'migration' => $name,
                'batch'     => $batch,
            ]);

            echo "  ✓ Migrated: {$name}" . PHP_EOL;
            $count++;
        }

        if ($count === 0) {
            echo "Nothing to migrate." . PHP_EOL;
        } else {
            echo PHP_EOL . "Ran {$count} migration(s)." . PHP_EOL;
        }
    }

    /**
     * Rollback all migrations.
     */
    public function down(): void
    {
        $ran = Capsule::table('migrations')->orderByDesc('id')->pluck('migration')->toArray();

        if (empty($ran)) {
            echo "Nothing to rollback." . PHP_EOL;
            return;
        }

        $fileMap = $this->getMigrationFileMap();
        $count = 0;

        foreach ($ran as $name) {
            if (!isset($fileMap[$name])) {
                continue;
            }

            $migration = require $fileMap[$name];
            $migration->down();

            Capsule::table('migrations')->where('migration', $name)->delete();

            echo "  ✗ Rolled back: {$name}" . PHP_EOL;
            $count++;
        }

        echo PHP_EOL . "Rolled back {$count} migration(s)." . PHP_EOL;
    }

    /**
     * Drop all tables and re-run migrations.
     */
    public function fresh(): void
    {
        echo "Dropping all tables..." . PHP_EOL;
        $this->down();

        Capsule::schema()->dropIfExists('migrations');
        $this->ensureMigrationsTable();

        echo PHP_EOL . "Re-running migrations..." . PHP_EOL;
        $this->up();
    }

    // ── Private Helpers ────────────────────────────────────────────

    private function ensureMigrationsTable(): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamp('ran_at')->useCurrent();
            });
        }
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        return $files ?: [];
    }

    private function getMigrationFileMap(): array
    {
        $map = [];
        foreach ($this->getMigrationFiles() as $file) {
            $map[basename($file)] = $file;
        }
        return $map;
    }
}

