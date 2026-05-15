<?php

declare(strict_types=1);

namespace CUK\Controllers;

use CUK\Security;

class TwoFactorController
{
    public function setup(): void
    {
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
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if (empty($secret) || empty($code)) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            exit;
        }

        if ($this->verifyCode($secret, $code)) {
            db()->update('users', ['twofa_secret' => $secret, 'twofa_actif' => 1], 'id = :id', ['id' => $userId]);
            Security::logActivity('2fa_active', 'Authentification à deux facteurs activée');
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Code invalide']);
        }
        exit;
    }

    public function disable(): void
    {
        Security::initSession();
        Security::requireAuth();

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Session expirée';
            header('Location: ?page=parametres');
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        db()->update('users', ['twofa_secret' => null, 'twofa_actif' => 0], 'id = :id', ['id' => $userId]);
        Security::logActivity('2fa_desactive', 'Authentification à deux facteurs désactivée');
        $_SESSION['success'] = '2FA désactivé';
        header('Location: ?page=parametres');
        exit;
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->verifyCode($secret, $code);
    }

    private function verifyCode(string $secret, string $code): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // TOTP verification: check current 30s window +/- 1 window
        $timeSlice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            if ($this->generateCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }
        return false;
    }

    private function generateCode(string $secret, float $timeSlice): string
    {
        $base32 = new \CUK\Base32();
        $key = $base32->decode($secret);
        $timeBytes = pack('J', (int)$timeSlice);
        $hash = hash_hmac('sha1', $timeBytes, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $code = (ord($hash[$offset]) & 0x7f) << 24
              | (ord($hash[$offset + 1]) & 0xff) << 16
              | (ord($hash[$offset + 2]) & 0xff) << 8
              | (ord($hash[$offset + 3]) & 0xff);
        $code %= 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    public static function getProvisioningUri(string $secret, string $username, string $issuer = 'CUK-Admin'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username)
             . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }
}
