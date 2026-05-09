<?php

if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
    echo "CUK-Admin - Centre Universitaire de Koulamoutou\n";
    echo "==============================================\n\n";
    echo "Pour lancer l'application, utilisez un serveur PHP:\n";
    echo "  php -S localhost:8000\n";
    echo "  php -S localhost:8080\n\n";
    echo "Ou utilisez PHPDesktop/PhpGtk.\n\n";
    echo "Fichiers nécessaires pour l'application:\n";
    echo "  - PHP 8.x\n";
    echo "  - Serveur MySQL/MariaDB\n";
    echo "  - Navigateur web\n\n";
    exit;
}

require_once __DIR__ . '/src/Database.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

session_start();

$dbConfig = require __DIR__ . '/config/database.php';

try {
    Database::getInstance();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données. Veuillez vérifier la configuration.");
}

$currentYear = db()->fetch("SELECT * FROM annees_academiques WHERE courante = 1");
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
</head>
<body>
    <div class="app-container">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <?php include __DIR__ . '/src/Views/login.php'; ?>
        <?php else: ?>
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <i class="bi bi-building"></i>
                        <span>CUK</span>
                    </div>
                    <button class="sidebar-toggle d-none d-md-block">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                <nav class="sidebar-nav">
                    <a href="?page=dashboard" class="nav-item active" data-page="dashboard">
                        <i class="bi bi-speedometer2"></i>
                        <span>Tableau de bord</span>
                    </a>
                    <a href="?page=etudiants" class="nav-item" data-page="etudiants">
                        <i class="bi bi-people"></i>
                        <span>Étudiants</span>
                    </a>
                    <a href="?page=notes" class="nav-item" data-page="notes">
                        <i class="bi bi-mortarboard"></i>
                        <span>Notes</span>
                    </a>
                    <a href="?page=absences" class="nav-item" data-page="absences">
                        <i class="bi bi-calendar-x"></i>
                        <span>Absences</span>
                    </a>
                    <a href="?page=filieres" class="nav-item" data-page="filieres">
                        <i class="bi bi-grid"></i>
                        <span>Filières & UE</span>
                    </a>
                    <a href="?page=disciplinarite" class="nav-item" data-page="disciplinarite">
                        <i class="bi bi-shield-exclamation"></i>
                        <span>Disciplinarité</span>
                    </a>
                    <a href="?page=orientations" class="nav-item" data-page="orientations">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Orientations</span>
                    </a>
                    <a href="?page=rapports" class="nav-item" data-page="rapports">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Rapports</span>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'root' || $_SESSION['user_role'] === 'administrateur'): ?>
                    <a href="?page=utilisateurs" class="nav-item" data-page="utilisateurs">
                        <i class="bi bi-person-badge"></i>
                        <span>Utilisateurs</span>
                    </a>
                    <a href="?page=parametres" class="nav-item" data-page="parametres">
                        <i class="bi bi-gear"></i>
                        <span>Paramètres</span>
                    </a>
                    <?php endif; ?>
                </nav>
                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
                            <span class="user-role"><?= ucfirst($_SESSION['user_role']) ?></span>
                        </div>
                    </div>
                    <a href="?logout=1" class="logout-btn" title="Déconnexion">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </aside>
            
            <main class="main-content">
                <header class="top-bar">
                    <div class="top-bar-left">
                        <h1 class="page-title">Tableau de bord</h1>
                        <?php if ($currentYear): ?>
                        <span class="annee-academique">
                            <i class="bi bi-calendar3"></i>
                            <?= htmlspecialchars($currentYear['annee']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="top-bar-right">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="Rechercher..." id="globalSearch">
                        </div>
                        <div class="notifications dropdown">
                            <button class="btn-icon dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span class="badge">3</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#">Nouveaux étudiants inscrits</a></li>
                                <li><a class="dropdown-item" href="#">Notes en attente de validation</a></li>
                            </ul>
                        </div>
                    </div>
                </header>
                
                <div class="content-area" id="mainContent">
                    <?php
                    $page = $_GET['page'] ?? 'dashboard';
                    $allowedPages = ['dashboard', 'etudiants', 'notes', 'absences', 'filieres', 'disciplinarite', 'orientations', 'rapports', 'utilisateurs', 'parametres'];
                    
                    if (in_array($page, $allowedPages)) {
                        if (file_exists(__DIR__ . '/src/Views/' . $page . '.php')) {
                            include __DIR__ . '/src/Views/' . $page . '.php';
                        }
                    } else {
                        include __DIR__ . '/src/Views/dashboard.php';
                    }
                    ?>
                </div>
            </main>
        <?php endif; ?>
    </div>

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
    <script src="assets/js/app.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.page === currentPage) {
                item.classList.add('active');
            }
        });
        
        const titles = {
            'dashboard': 'Tableau de bord',
            'etudiants': 'Étudiants',
            'notes': 'Notes',
            'absences': 'Absences',
            'filieres': 'Filières & UE',
            'disciplinarite': 'Disciplinarité',
            'orientations': 'Orientations',
            'rapports': 'Rapports',
            'utilisateurs': 'Utilisateurs',
            'parametres': 'Paramètres'
        };
        
        const titleEl = document.querySelector('.page-title');
        if (titleEl && titles[currentPage]) {
            titleEl.textContent = titles[currentPage];
        }
    });
    </script>
</body>
</html>