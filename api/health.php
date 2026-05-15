<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use CUK\Security;

Security::initSession();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$status = 'ok';
$httpCode = 200;
$checks = [];

// Database
try {
    $db = \CUK\Database::getInstance();
    $db->query("SELECT 1");
    $checks['database'] = ['status' => 'ok'];
} catch (\Throwable $e) {
    $checks['database'] = ['status' => 'error'];
    $status = 'error';
}

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
echo json_encode($response);
