<?php

declare(strict_types=1);

namespace CUK;

class Security
{
    private static ?string $nonce = null;

    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.gc_maxlifetime', '7200');

            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', '1');
            }

            $redisHost = getenv('REDIS_HOST') ?: '';
            if ($redisHost) {
                ini_set('session.save_handler', 'redis');
                $redisPort = getenv('REDIS_PORT') ?: '6379';
                $redisPrefix = getenv('REDIS_PREFIX') ?: 'cuk_session:';
                ini_set('session.save_path', "tcp://{$redisHost}:{$redisPort}?prefix={$redisPrefix}");
            }

            session_start();
        }
    }

    public static function destroySession(): void
    {
        self::initSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }

    public static function nonceAttr(): string
    {
        return 'nonce="' . self::nonce() . '"';
    }

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        $valid = hash_equals($_SESSION['_csrf_token'], $token);
        if ($valid) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $valid;
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::generateCsrfToken() . '">';
    }

    public static function csrfMeta(): string
    {
        return '<meta name="csrf-token" content="' . self::generateCsrfToken() . '">';
    }

    public static function requireAuth(): void
    {
        self::initSession();
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireAuth();
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            header('Location: ?page=dashboard');
            exit;
        }
    }

    public static function requirePasswordChange(): void
    {
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['must_change_password'])) {
            $currentPage = $_GET['page'] ?? '';
            if ($currentPage !== 'changer_mot_de_passe') {
                header('Location: ?page=changer_mot_de_passe');
                exit;
            }
        }
    }

    public static function checkIdleTimeout(int $timeout = 7200): void
    {
        self::initSession();
        if (empty($_SESSION['user_id'])) return;

        $now = time();
        $lastActivity = $_SESSION['last_activity'] ?? $now;

        if ($now - $lastActivity > $timeout) {
            self::destroySession();
            $_SESSION['error'] = 'Session expirée pour inactivité';
            header('Location: index.php');
            exit;
        }

        $_SESSION['last_activity'] = $now;
    }

    public static function sendSecurityHeaders(): void
    {
        $nonce = self::nonce();

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), fullscreen=(self)');

        $csp = "default-src 'self'; "
             . "script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://cdn.datatables.net 'nonce-{$nonce}'; "
             . "style-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net 'nonce-{$nonce}'; "
             . "img-src 'self' data: blob:; "
             . "font-src 'self' https://cdn.jsdelivr.net; "
             . "connect-src 'self'; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'";
        header("Content-Security-Policy: {$csp}");

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    public static function h(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function validateEnum(string $value, array $allowed, string $default = ''): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function validateInt(mixed $value, int $default = 0): int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
    }

    public static function validateEmail(mixed $value): string
    {
        $v = filter_var((string)$value, FILTER_VALIDATE_EMAIL);
        return $v !== false ? $v : '';
    }

    public static function validateDate(string $date): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return ($d && $d->format('Y-m-d') === $date) ? $date : '';
    }

    public static function safeJson(mixed $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    public static function rateLimitCheck(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        self::initSession();
        $now = time();
        $window = $_SESSION[$key] ?? ['count' => 0, 'time' => $now];

        if ($now - $window['time'] > $windowSeconds) {
            $window = ['count' => 0, 'time' => $now];
        }

        $window['count']++;
        $_SESSION[$key] = $window;

        if ($window['count'] > $maxAttempts) {
            return false;
        }

        // Database-backed rate limiting as secondary check for IP-based attacks
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip) {
                $attempts = db()->fetch(
                    "SELECT COUNT(*) as c FROM journal_activite
                     WHERE action = :action AND ip_address = :ip
                     AND created_at > datetime('now', :window)",
                    [
                        'action' => $key,
                        'ip' => $ip,
                        'window' => '-' . $windowSeconds . ' seconds'
                    ]
                );
                if (($attempts['c'] ?? 0) > $maxAttempts) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable - session check is sufficient fallback
        }

        return true;
    }

    public static function accountLockout(string $username): bool
    {
        $lockouts = db()->fetch(
            "SELECT COUNT(*) as count FROM journal_activite
             WHERE action = 'connexion_echouee' AND details = :username
             AND created_at > datetime('now', '-15 minutes')",
            ['username' => $username]
        );

        return ($lockouts['count'] ?? 0) >= 10;
    }

    public static function showSuccess(): void
    {
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> ' . self::h($_SESSION['success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            unset($_SESSION['success']);
        }
    }

    public static function showError(): void
    {
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> ' . self::h($_SESSION['error']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            unset($_SESSION['error']);
        }
    }

    public static function logActivity(string $action, ?string $details = null, ?string $table = null, ?int $idConcerne = null): void
    {
        if (empty($_SESSION['user_id'])) return;
        db()->insert('journal_activite', [
            'user_id' => $_SESSION['user_id'],
            'action' => $action,
            'table_concernee' => $table,
            'id_concerne' => $idConcerne,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }
}
