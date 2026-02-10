<?php

/**
 * Bootstrap / Helper Functions
 *
 * Load this file early in your entry point:
 *   require __DIR__ . '/config/bootstrap.php';
 */

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// ── Autoload & .env ────────────────────────────────────────────────

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// ── Helper Functions ───────────────────────────────────────────────

/**
 * Get an environment variable with an optional default.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Cast common string representations
    return match (strtolower($value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        'empty', '(empty)' => '',
        default             => $value,
    };
}

/**
 * Get the Eloquent database manager instance.
 */
function db(): Capsule
{
    return Capsule::getFacadeRoot() ?? new Capsule;
}

/**
 * Dump variables and die.
 */
function dd(mixed ...$vars): never
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die(1);
}

/**
 * Dump variables without dying.
 */
function dump(mixed ...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
}

/**
 * Get the project root path, optionally appending a sub-path.
 */
function base_path(string $path = ''): string
{
    $root = realpath(__DIR__ . '/..');
    return $path ? $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $root;
}

/**
 * Get the storage path, optionally appending a sub-path.
 */
function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
}

/**
 * Get the config path, optionally appending a sub-path.
 */
function config_path(string $path = ''): string
{
    return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
}

/**
 * Safely encode data to JSON.
 */
function json(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
{
    return json_encode($data, $flags);
}

/**
 * Generate a simple log entry to a file.
 */
function logger(string $message, string $level = 'INFO', string $file = 'app.log'): void
{
    $logPath = storage_path('logs' . DIRECTORY_SEPARATOR . $file);
    $dir = dirname($logPath);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Abort with an HTTP status code and optional message.
 */
function abort(int $code = 500, string $message = ''): never
{
    http_response_code($code);
    die($message ?: "Error {$code}");
}

/**
 * Send a JSON response and terminate.
 */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json($data);
    die();
}

/**
 * Redirect to a URL.
 */
function redirect(string $url, int $status = 302): never
{
    header("Location: {$url}", true, $status);
    die();
}

/**
 * Get the current timestamp in a given format.
 */
function now(string $format = 'Y-m-d H:i:s'): string
{
    return date($format);
}

/**
 * Escape HTML entities for safe output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Eloquent ORM Boot ──────────────────────────────────────────────

$capsule = new Capsule;
$dbConfig = require config_path('database.php');
$driver = $dbConfig['driver'];

if ($driver === 'sqlite') {
    $path = $dbConfig['connections']['sqlite']['path'];
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!file_exists($path)) {
        touch($path);
    }
    $capsule->addConnection([
        'driver'   => 'sqlite',
        'database' => $path,
        'prefix'   => '',
    ]);
} else {
    $mysql = $dbConfig['connections']['mysql'];
    $capsule->addConnection([
        'driver'    => 'mysql',
        'host'      => $mysql['host'],
        'port'      => $mysql['port'],
        'database'  => $mysql['database'],
        'username'  => $mysql['username'],
        'password'  => $mysql['password'],
        'charset'   => $mysql['charset'],
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]);
}

$capsule->setAsGlobal();
$capsule->bootEloquent();
