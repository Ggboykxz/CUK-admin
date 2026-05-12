<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        $error = 'Session expirée, veuillez réessayer';
    } elseif (!Security::rateLimitCheck('login_attempts', 5, 300)) {
        $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Veuillez remplir tous les champs';
        } else {
            $user = db()->fetch(
                "SELECT * FROM users WHERE username = :username AND actif = 1",
                ['username' => $username]
            );

            if ($user && password_verify($password, $user['password_hash'])) {
                Security::regenerateSession();

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];

                unset($_SESSION['login_attempts']);

                db()->update(
                    'users',
                    ['derniere_connexion' => date('Y-m-d H:i:s')],
                    'id = :id',
                    ['id' => $user['id']]
                );

                Security::logActivity('connexion', 'Connexion réussie');

                header('Location: index.php');
                exit;
            } else {
                $error = 'Identifiants incorrects';
            }
        }
    }
}
?>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <i class="bi bi-building"></i>
            </div>
            <h1>CUK-Admin</h1>
            <p class="subtitle">Centre Universitaire de Koulamoutou</p>
        </div>
        
        <form method="POST" class="login-form">
            <?= Security::csrfField() ?>
            <?php if ($error) : ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= Security::h($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">
                    <i class="bi bi-person"></i>
                    Nom d'utilisateur
                </label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="bi bi-lock"></i>
                    Mot de passe
                </label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Se connecter
            </button>
        </form>
        
        <div class="login-footer">
            <p>Système de gestion universitaire</p>
            <p class="version">Version 1.0.0</p>
        </div>
    </div>
</div>


