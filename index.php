<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Security.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/helpers.php';

Security::initSession();

if (isset($_GET['logout'])) {
    Security::destroySession();
    header('Location: index.php');
    exit;
}

Security::sendSecurityHeaders();
$nonce = Security::nonce();

$config = require __DIR__ . '/config/database.php';

try {
    $currentYear = db()->fetch("SELECT * FROM annees_academiques WHERE courante = 1");
} catch (\PDOException $e) {
    $currentYear = null;
}

// Maintenance mode check
if (getenv('MAINTENANCE_MODE') === 'true' && !isset($_GET['maintenance'])) {
    http_response_code(503);
    header('Retry-After: 3600');
    ?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Maintenance - CUK-Admin</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f7fa;font-family:sans-serif;}.card{text-align:center;padding:60px;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.1);}h1{color:#1e3a5f;}p{color:#666;}</style></head><body><div class="card"><h1><i class="bi bi-tools"></i> Maintenance en cours</h1><p>L'application est temporairement indisponible pour maintenance.<br>Merci de réessayer dans quelques minutes.</p><p class="text-muted small">CUK-Admin - Centre Universitaire de Koulamoutou</p></div></body></html>
    <?php exit;
}

// Error handler
set_exception_handler(function (\Throwable $e) {
    Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    if (getenv('APP_ENV') === 'production') {
        http_response_code(500);
        include __DIR__ . '/src/Views/errors/500.php';
        exit;
    }
    throw $e;
});
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        Logger::warning($message, ['file' => $file, 'line' => $line]);
    }
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CUK-Admin | Centre Universitaire de Koulamoutou</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛</text></svg>">
    <?= Security::csrfMeta() ?>
</head>
<body>
    <div class="app-container">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <?php include __DIR__ . '/src/Views/login.php'; ?>
        <?php else: ?>
            <?php Security::requirePasswordChange(); ?>
            <?php include __DIR__ . '/src/Views/layouts/sidebar.php'; ?>
            <main class="main-content">
                <header class="top-bar">
                    <div class="top-bar-left">
                        <h1 class="page-title">Tableau de bord</h1>
                        <?php if ($currentYear): ?>
                        <span class="annee-academique">
                            <i class="bi bi-calendar3"></i>
                            <?= Security::h($currentYear['annee'] ?? '') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="top-bar-right">
                        <div class="search-box" style="position:relative;">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="Rechercher..." id="globalSearch">
                            <?php include __DIR__ . '/src/Views/components/search_results.php'; ?>
                        </div>
                    </div>
                </header>
                <div class="content-area" id="mainContent">
                    <?php
                    $page = $_GET['page'] ?? 'dashboard';
                    $allowedPages = ['dashboard', 'etudiants', 'notes', 'absences', 'filieres', 'disciplinarite', 'orientations', 'rapports', 'utilisateurs', 'parametres', 'messages', 'cours', 'finances', 'jury', 'portal', 'changer_mot_de_passe'];
                    if (in_array($page, $allowedPages, true)) {
                        $viewFile = __DIR__ . '/src/Views/' . $page . '.php';
                        if (file_exists($viewFile)) {
                            Security::showSuccess();
                            Security::showError();
                            include $viewFile;
                        }
                    } else {
                        include __DIR__ . '/src/Views/dashboard.php';
                    }
                    ?>
                </div>
            </main>
        <?php endif; ?>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="modal fade" id="modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"></div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" <?= Security::nonceAttr() ?>></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" <?= Security::nonceAttr() ?>></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" <?= Security::nonceAttr() ?>></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js" <?= Security::nonceAttr() ?>></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" <?= Security::nonceAttr() ?>></script>
    <script src="assets/js/app.js" <?= Security::nonceAttr() ?>></script>
</body>
</html>
