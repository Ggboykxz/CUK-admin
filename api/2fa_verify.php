<?php

declare(strict_types=1);

/**
 * 2FA verification endpoint.
 * Redirects to the centralized TwoFactorController.
 * Kept for backward compatibility.
 */
require_once __DIR__ . '/../src/bootstrap.php';

use CUK\Controllers\TwoFactorController;
use CUK\Security;

Security::initSession();
Security::requireAuth();

header('Content-Type: application/json');

if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expirée']);
    exit;
}

$controller = new TwoFactorController();
$controller->setup();
