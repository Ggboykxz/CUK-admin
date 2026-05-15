<?php

declare(strict_types=1);

use CUK\Database;
use CUK\Security;

if (!function_exists('db')) {
    function db(): Database { return Database::getInstance(); }
}

if (!function_exists('h')) {
    function h(mixed $value): string { return Security::h($value); }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string { return Security::csrfField(); }
}

if (!function_exists('nonce')) {
    function nonce(): string { return Security::nonce(); }
}

if (!function_exists('nonce_attr')) {
    function nonce_attr(): string { return Security::nonceAttr(); }
}

if (!function_exists('asset')) {
    function asset(string $path): string { return $path; }
}

if (!function_exists('page_url')) {
    function page_url(string $page): string { return '?page=' . $page; }
}

if (!function_exists('flash_success')) {
    function flash_success(string $message): void { $_SESSION['success'] = $message; }
}

if (!function_exists('flash_error')) {
    function flash_error(string $message): void { $_SESSION['error'] = $message; }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void { header("Location: $url"); exit; }
}

if (!function_exists('current_page')) {
    function current_page(): string { return \CUK\Router::currentPage(); }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('load_env')) {
    function load_env(): void {
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) return;
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                $pos = strpos($line, '=');
                $key = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

load_env();

spl_autoload_register(function (string $class): void {
    $map = [
        'Security' => 'CUK\\Security',
        'Database' => 'CUK\\Database',
        'Logger' => 'CUK\\Logger',
        'Router' => 'CUK\\Router',
        'View' => 'CUK\\View',
        'Base32' => 'CUK\\Base32',
    ];
    if (isset($map[$class])) {
        class_alias($map[$class], $class);
    }
}, true, false);
