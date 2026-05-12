<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $error = 'Mot de passe actuel incorrect';
    } elseif (strlen($new) < 8) {
        $error = 'Le nouveau mot de passe doit faire au moins 8 caractères';
    } elseif (!preg_match('/[A-Z]/', $new)) {
        $error = 'Le mot de passe doit contenir au moins une majuscule';
    } elseif (!preg_match('/[0-9]/', $new)) {
        $error = 'Le mot de passe doit contenir au moins un chiffre';
    } elseif ($new !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (password_verify($new, $user['password_hash'])) {
        $error = 'Le nouveau mot de passe doit être différent de l\'actuel';
    } else {
        db()->update('users', [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT)
        ], 'id = :id', ['id' => $userId]);

        unset($_SESSION['must_change_password']);
        Security::logActivity('mot_de_passe_change', 'Mot de passe modifié');
        $success = 'Mot de passe modifié avec succès';

        $knownHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if ($user['password_hash'] === $knownHash) {
            $_SESSION['success'] = $success;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center py-4">
                    <i class="bi bi-shield-lock" style="font-size:48px;"></i>
                    <h4 class="mt-2 mb-0">Changement de mot de passe</h4>
                    <p class="mb-0 opacity-75 small">Pour des raisons de sécurité, vous devez changer votre mot de passe</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= Security::h($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?= Security::h($success) ?></div>
                    <a href="index.php" class="btn btn-primary w-100"><i class="bi bi-arrow-right"></i> Accéder à l'application</a>
                    <?php else: ?>
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8" pattern="(?=.*[A-Z])(?=.*[0-9]).{8,}">
                            <small class="text-muted">8 caractères min, 1 majuscule, 1 chiffre</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Changer le mot de passe</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
