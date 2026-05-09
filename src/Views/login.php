<?php
require_once __DIR__ . '/../src/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            
            db()->update('users', 
                ['derniere_connexion' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );
            
            db()->insert('journal_activite', [
                'user_id' => $user['id'],
                'action' => 'connexion',
                'details' => 'Connexion réussie',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
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
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
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

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    padding: 20px;
}

.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 420px;
    overflow: hidden;
}

.login-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.login-logo {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
}

.login-header h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.subtitle {
    font-size: 14px;
    opacity: 0.9;
    margin: 10px 0 0;
}

.login-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-group label i {
    margin-right: 8px;
    color: #2d5a87;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #2d5a87;
}

.btn-login {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-login:hover {
    transform: translateY(-2px);
}

.login-footer {
    background: #f5f7fa;
    padding: 20px;
    text-align: center;
    color: #666;
    font-size: 13px;
}

.version {
    margin-top: 5px;
    color: #999;
}
</style>