<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;

Security::initSession();
Security::requireAuth();

header('Content-Type: application/json');

if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expirée']);
    exit;
}

$secret = $_POST['secret'] ?? '';
$code = $_POST['code'] ?? '';

if (empty($secret) || empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// TOTP verification
$code = trim($code);
$timeSlice = floor(time() / 30);
$valid = false;

for ($i = -1; $i <= 1; $i++) {
    $ts = (int)($timeSlice + $i);
    $key = base32_decode($secret);
    $timeBytes = pack('J', $ts);
    $hash = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord($hash[19]) & 0x0f;
    $generated = (ord($hash[$offset]) & 0x7f) << 24
               | (ord($hash[$offset + 1]) & 0xff) << 16
               | (ord($hash[$offset + 2]) & 0xff) << 8
               | (ord($hash[$offset + 3]) & 0xff);
    $generated %= 1000000;
    $generated = str_pad((string)$generated, 6, '0', STR_PAD_LEFT);

    if (hash_equals($generated, $code)) {
        $valid = true;
        break;
    }
}

if ($valid) {
    db()->update('users', ['2fa_secret' => $secret, '2fa_actif' => 1], 'id = :id', ['id' => $userId]);
    Security::logActivity('2fa_active', 'Authentification à deux facteurs activée');
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Code invalide. Vérifiez l\'heure de votre appareil.']);
}

function base32_decode(string $data): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper($data);
    $data = str_replace('=', '', $data);
    $result = '';
    $buffer = 0;
    $bitsLeft = 0;

    for ($i = 0; $i < strlen($data); $i++) {
        $val = strpos($chars, $data[$i]);
        if ($val === false) continue;
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        if ($bitsLeft >= 8) {
            $bitsLeft -= 8;
            $result .= chr(($buffer >> $bitsLeft) & 0xFF);
        }
    }

    return $result;
}
