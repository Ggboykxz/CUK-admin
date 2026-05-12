<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;
use CUK\Database;

Security::initSession();
Security::requireAuth();

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id || !isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier invalide']);
    exit;
}

$file = $_FILES['photo'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format non autorisé (jpg, png, gif, webp)']);
    exit;
}

$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Fichier trop volumineux (max 2 Mo)']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'etu_' . $id . '_' . time() . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
    exit;
}

db()->update('etudiants', ['photo_path' => 'uploads/photos/' . $filename], 'id = :id', ['id' => $id]);

echo json_encode(['success' => true, 'path' => 'uploads/photos/' . $filename]);
