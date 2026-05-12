<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;
use CUK\Database;
use CUK\Router;
use CUK\View;

Security::initSession();

if (isset($_GET['logout'])) {
    Security::destroySession();
    header('Location: index.php');
    exit;
}

Security::sendSecurityHeaders();

$dbConfig = require __DIR__ . '/../config/database.php';

try {
    $currentYear = db()->fetch("SELECT * FROM annees_academiques WHERE courante = 1");
} catch (\PDOException $e) {
    $currentYear = null;
}

View::share('currentYear', $currentYear);

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = Router::allowedPages();
$isPortal = ($page === 'portal');
$page = in_array($page, $allowedPages, true) ? $page : 'dashboard';

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
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛</text></svg>">
    <?= Security::csrfMeta() ?>
</head>
<body>
    <div class="app-container">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <?php
            $error = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require __DIR__ . '/../src/Controllers/AuthController.php';
                $ctrl = new \CUK\Controllers\AuthController();
                $ctrl->login();
            }
            ?>
            <?php include __DIR__ . '/../src/Views/login.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/../src/Views/layouts/sidebar.php'; ?>

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
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="Rechercher..." id="globalSearch">
                        </div>
                        <button class="btn-icon position-relative" onclick="loadNotifications()" title="Notifications" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger" id="notifBadge" style="display:none;">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="notifDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><div class="dropdown-item text-muted" id="notifEmpty">Aucune notification</div></li>
                        </ul>
                    </div>
                </header>

                <div class="content-area" id="mainContent">
                    <?php
                    $viewFile = __DIR__ . '/../src/Views/' . $page . '.php';
                    if (file_exists($viewFile)) {
                        Security::showSuccess();
                        Security::showError();
                        include $viewFile;
                    } else {
                        echo '<div class="empty-state"><i class="bi bi-file-earmark"></i><p>Page non trouvée</p></div>';
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
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script>
    function loadNotifications() {
        fetch('api/notifications.php')
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('notifBadge');
                const dropdown = document.getElementById('notifDropdown');
                const empty = document.getElementById('notifEmpty');

                if (data.length > 0) {
                    badge.textContent = data.length;
                    badge.style.display = '';
                    let html = '';
                    data.forEach(n => {
                        html += '<li><a class="dropdown-item" href="' + (n.lien || '#') + '"><strong>' + escapeHtml(n.titre) + '</strong><br><small>' + escapeHtml(n.message) + '</small></a></li>';
                    });
                    empty.outerHTML = html;
                } else {
                    badge.style.display = 'none';
                }
            });
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    setInterval(loadNotifications, 30000);
    setTimeout(loadNotifications, 2000);
    </script>
</body>
</html>
