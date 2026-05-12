<?php

declare(strict_types=1);

namespace CUK\Controllers;

use CUK\Security;
use CUK\Logger;

class AuthController
{
    public function login(): void
    {
        Security::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Session expirée, veuillez réessayer';
            return;
        }

        if (!Security::rateLimitCheck('login_attempts', 5, 300)) {
            $_SESSION['error'] = 'Trop de tentatives. Réessayez dans quelques minutes.';
            Logger::warning('Rate limit atteint pour login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Veuillez remplir tous les champs';
            return;
        }

        if (Security::accountLockout($username)) {
            $_SESSION['error'] = 'Compte temporairement verrouillé. Réessayez dans 15 minutes.';
            Logger::warning("Compte verrouillé: {$username}");
            return;
        }

        $user = db()->fetch(
            "SELECT * FROM users WHERE username = :username AND actif = 1",
            ['username' => $username]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            Security::regenerateSession();
            unset($_SESSION['login_attempts']);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['last_activity'] = time();

            // Force password change if still using default password
            $defaultHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            if ($user['password_hash'] === $defaultHash) {
                $_SESSION['must_change_password'] = true;
            }

            db()->update('users', ['derniere_connexion' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
            Security::logActivity('connexion', 'Connexion réussie');
            Logger::info("Connexion réussie: {$username}");

            header('Location: index.php');
            exit;
        }

        Logger::warning("Tentative échouée pour: {$username}");
        Security::logActivity('connexion_echouee', "Tentative échouée pour: {$username}");
        $_SESSION['error'] = 'Identifiants incorrects';
    }
}
