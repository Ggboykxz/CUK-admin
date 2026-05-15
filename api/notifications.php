<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use CUK\Security;
use CUK\Database;

Security::initSession();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$notifs = db()->fetchAll(
    "SELECT id, type, titre, message, lien, created_at 
     FROM notifications 
     WHERE (user_id = :user_id OR user_id IS NULL) AND lu = 0 
     ORDER BY created_at DESC 
     LIMIT 10",
    ['user_id' => $_SESSION['user_id']]
);

echo json_encode($notifs, JSON_UNESCAPED_UNICODE);
