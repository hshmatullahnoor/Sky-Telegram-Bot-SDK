<?php

namespace Classes;

class Log
{
    private static ?Log $instance = null;

    private string $channel;
    private string $level;
    private string $path;
    private string $file;
    private int    $maxFiles;
    private string $dateFormat;

    private const LEVELS = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
        'alert'     => 6,
        'emergency' => 7,
    ];

    private function __construct()
    {
        $this->channel    = env('LOG_CHANNEL', 'file');
        $this->level      = strtolower(env('LOG_LEVEL', 'debug'));
        $this->path       = env('LOG_PATH', 'storage/logs');
        $this->file       = env('LOG_FILE', 'app.log');
        $this->maxFiles   = (int) env('LOG_MAX_FILES', 7);
        $this->dateFormat = env('LOG_DATE_FORMAT', 'Y-m-d H:i:s');

        // Convert relative path to absolute using base_path
        if (!str_starts_with($this->path, '/')) {
            $this->path = base_path($this->path);
        }

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Level Methods ──────────────────────────────────────────────

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->write('info', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::getInstance()->write('notice', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->write('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->write('critical', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::getInstance()->write('alert', $message, $context);
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::getInstance()->write('emergency', $message, $context);
    }

    // ── Core Writer ────────────────────────────────────────────────

    private function write(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date($this->dateFormat);
        $upperLevel = strtoupper($level);
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$timestamp}] [{$upperLevel}] {$message}{$contextStr}" . PHP_EOL;

        match ($this->channel) {
            'file'    => $this->writeToFile($line),
            'daily'   => $this->writeToDailyFile($line),
            'stderr'  => file_put_contents('php://stderr', $line),
            'stdout'  => print($line),
            'null'    => null,
            default   => $this->writeToFile($line),
        };
    }

    private function shouldLog(string $level): bool
    {
        return (self::LEVELS[$level] ?? 0) >= (self::LEVELS[$this->level] ?? 0);
    }

    // ── File Channels ──────────────────────────────────────────────

    private function writeToFile(string $line): void
    {
        $filepath = $this->path . DIRECTORY_SEPARATOR . $this->file;
        file_put_contents($filepath, $line, FILE_APPEND | LOCK_EX);
    }

    private function writeToDailyFile(string $line): void
    {
        $name = pathinfo($this->file, PATHINFO_FILENAME);
        $ext  = pathinfo($this->file, PATHINFO_EXTENSION) ?: 'log';
        $date = date('Y-m-d');

        $filepath = $this->path . DIRECTORY_SEPARATOR . "{$name}-{$date}.{$ext}";
        file_put_contents($filepath, $line, FILE_APPEND | LOCK_EX);

        $this->rotateDaily($name, $ext);
    }

    private function rotateDaily(string $name, string $ext): void
    {
        $pattern = $this->path . DIRECTORY_SEPARATOR . "{$name}-*.{$ext}";
        $files = glob($pattern);

        if ($files && count($files) > $this->maxFiles) {
            sort($files);
            $toDelete = array_slice($files, 0, count($files) - $this->maxFiles);
            foreach ($toDelete as $old) {
                @unlink($old);
            }
        }
    }

    // ── Utility ────────────────────────────────────────────────────

    public static function clear(): void
    {
        $self = self::getInstance();
        $filepath = $self->path . DIRECTORY_SEPARATOR . $self->file;
        if (file_exists($filepath)) {
            file_put_contents($filepath, '');
        }
    }

    public static function getLogPath(): string
    {
        $self = self::getInstance();
        return $self->path . DIRECTORY_SEPARATOR . $self->file;
    }

    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
