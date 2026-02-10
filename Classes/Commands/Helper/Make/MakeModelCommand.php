<?php

namespace Classes\Commands\Helper\Make;

class MakeModelCommand
{
    private string $modelsPath;

    public function __construct(?string $modelsPath = null)
    {
        $this->modelsPath = $modelsPath ?? base_path('database/Models');
    }

    /**
     * Create a new Eloquent model file.
     */
    public function handle(string $name): void
    {
        if (empty($name)) {
            echo "Usage: php helper.php make:model <Name>" . PHP_EOL;
            return;
        }

        $name = ucfirst($name);
        $filePath = $this->modelsPath . DIRECTORY_SEPARATOR . "{$name}.php";

        if (file_exists($filePath)) {
            echo "  ✗ Model already exists: {$name}.php" . PHP_EOL;
            return;
        }

        if (!is_dir($this->modelsPath)) {
            mkdir($this->modelsPath, 0755, true);
        }

        $table = $this->toTableName($name);
        $stub = $this->getStub($name, $table);

        file_put_contents($filePath, $stub);
        echo "  ✓ Model created: database/Models/{$name}.php" . PHP_EOL;
    }

    private function getStub(string $name, string $table): string
    {
        return <<<PHP
<?php

namespace Database\Models;

use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    protected \$table = '{$table}';

    protected \$fillable = [
        //
    ];

    protected \$casts = [
        //
    ];
}

PHP;
    }

    /**
     * Convert PascalCase model name to snake_case plural table name.
     */
    private function toTableName(string $name): string
    {
        // PascalCase to snake_case
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        // Simple pluralize
        if (str_ends_with($snake, 'y') && !str_ends_with($snake, 'ay') && !str_ends_with($snake, 'ey') && !str_ends_with($snake, 'oy') && !str_ends_with($snake, 'uy')) {
            return substr($snake, 0, -1) . 'ies';
        }
        if (str_ends_with($snake, 's') || str_ends_with($snake, 'sh') || str_ends_with($snake, 'ch') || str_ends_with($snake, 'x')) {
            return $snake . 'es';
        }
        return $snake . 's';
    }
}
