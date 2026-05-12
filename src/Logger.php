<?php

declare(strict_types=1);

namespace CUK;

class Logger
{
    private static ?string $logDir = null;

    private static function init(): void
    {
        if (self::$logDir !== null) return;
        self::$logDir = __DIR__ . '/../runtime/logs/';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        self::init();
        $date = date('Y-m-d H:i:s');
        $trace = '';
        if (!empty($context)) {
            $trace = ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line = "[{$date}] {$level}: {$message}{$trace}" . PHP_EOL;
        file_put_contents(self::$logDir . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);

        // Also log to database for critical errors
        if (in_array($level, ['ERROR', 'CRITICAL'], true)) {
            try {
                db()->insert('journal_activite', [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'action' => 'error_' . strtolower($level),
                    'details' => substr($message, 0, 500),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $e) {
                // Silent fail - DB might be unavailable
            }
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    public static function access(): void
    {
        self::init();
        $date = date('Y-m-d H:i:s');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user = $_SESSION['user_id'] ?? 'anon';
        $line = "[{$date}] {$ip} {$method} {$uri} user={$user}" . PHP_EOL;
        file_put_contents(self::$logDir . 'access.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function rotate(): void
    {
        self::init();
        $files = glob(self::$logDir . '*.log');
        $maxDays = 30;
        foreach ($files as $file) {
            if (filemtime($file) < time() - $maxDays * 86400) {
                unlink($file);
            }
        }
    }

    public static function getRecent(int $lines = 100): array
    {
        self::init();
        $file = self::$logDir . date('Y-m-d') . '.log';
        if (!file_exists($file)) return [];
        $content = file($file);
        $content = array_slice($content, -$lines);
        return array_map('trim', $content);
    }
}
