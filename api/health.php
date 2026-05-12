<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$status = 'ok';
$httpCode = 200;
$checks = [];

// PHP version
$checks['php_version'] = [
    'status' => PHP_VERSION_ID >= 80100 ? 'ok' : 'error',
    'value' => PHP_VERSION
];

// Database
try {
    $db = \CUK\Database::getInstance();
    $db->query("SELECT 1");
    $checks['database'] = ['status' => 'ok', 'driver' => $db->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME)];
} catch (\Throwable $e) {
    $checks['database'] = ['status' => 'error', 'error' => $e->getMessage()];
    $status = 'error';
}

// Redis session
$redisHost = getenv('REDIS_HOST');
if ($redisHost) {
    try {
        $redis = new \Redis();
        $redis->connect($redisHost, (int)(getenv('REDIS_PORT') ?: 6379), 2);
        $checks['redis'] = ['status' => 'ok', 'ping' => $redis->ping()];
        $redis->close();
    } catch (\Throwable $e) {
        $checks['redis'] = ['status' => 'error', 'error' => $e->getMessage()];
        $status = 'error';
    }
} else {
    $checks['redis'] = ['status' => 'skipped', 'reason' => 'REDIS_HOST not set'];
}

// Disk space
$diskFree = disk_free_space('/');
$diskTotal = disk_total_space('/');
$diskPercent = round((1 - $diskFree / $diskTotal) * 100);
$checks['disk'] = [
    'status' => $diskPercent < 90 ? 'ok' : 'warning',
    'percent_used' => $diskPercent,
    'free' => round($diskFree / 1073741824, 2) . ' GB'
];

// Writable directories
$dirs = ['runtime/logs', 'database', 'database/backups', 'uploads/photos'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    $checks["dir_{$dir}"] = [
        'status' => is_writable($path) ? 'ok' : 'error',
        'writable' => is_writable($path)
    ];
    if (!is_writable($path)) {
        $status = 'error';
    }
}

// Database size
try {
    $config = require __DIR__ . '/../config/database.php';
    if (($config['driver'] ?? 'sqlite') === 'sqlite') {
        $dbPath = __DIR__ . '/../' . ($config['database'] ?? 'database/cuk_admin.sqlite');
        if (file_exists($dbPath)) {
            $checks['database_size'] = ['status' => 'ok', 'size' => round(filesize($dbPath) / 1048576, 2) . ' MB'];
        }
    }
} catch (\Throwable $e) {
    // ignore
}

// Uptime
$checks['uptime'] = ['status' => 'ok', 'since' => date('Y-m-d H:i:s', filemtime(__FILE__))];

$response = [
    'status' => $status,
    'timestamp' => date('c'),
    'environment' => getenv('APP_ENV') ?: 'development',
    'checks' => $checks
];

if ($status !== 'ok') {
    $httpCode = 503;
}

http_response_code($httpCode);
echo json_encode($response, JSON_PRETTY_PRINT);
