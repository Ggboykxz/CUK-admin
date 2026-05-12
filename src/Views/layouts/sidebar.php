<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="bi bi-building"></i>
            <span>CUK</span>
        </div>
        <button class="sidebar-toggle d-none d-md-block" aria-label="Réduire">
            <i class="bi bi-list"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
        <a href="?page=dashboard" class="nav-item <?= current_page() === 'dashboard' ? 'active' : '' ?>" data-page="dashboard">
            <i class="bi bi-speedometer2"></i>
            <span>Tableau de bord</span>
        </a>
        <a href="?page=etudiants" class="nav-item <?= current_page() === 'etudiants' ? 'active' : '' ?>" data-page="etudiants">
            <i class="bi bi-people"></i>
            <span>Étudiants</span>
        </a>
        <a href="?page=notes" class="nav-item <?= current_page() === 'notes' ? 'active' : '' ?>" data-page="notes">
            <i class="bi bi-mortarboard"></i>
            <span>Notes</span>
        </a>
        <a href="?page=absences" class="nav-item <?= current_page() === 'absences' ? 'active' : '' ?>" data-page="absences">
            <i class="bi bi-calendar-x"></i>
            <span>Absences</span>
        </a>
        <a href="?page=filieres" class="nav-item <?= current_page() === 'filieres' ? 'active' : '' ?>" data-page="filieres">
            <i class="bi bi-grid"></i>
            <span>Filières & UE</span>
        </a>
        <a href="?page=disciplinarite" class="nav-item <?= current_page() === 'disciplinarite' ? 'active' : '' ?>" data-page="disciplinarite">
            <i class="bi bi-shield-exclamation"></i>
            <span>Disciplinarité</span>
        </a>
        <a href="?page=orientations" class="nav-item <?= current_page() === 'orientations' ? 'active' : '' ?>" data-page="orientations">
            <i class="bi bi-arrow-left-right"></i>
            <span>Orientations</span>
        </a>
        <a href="?page=rapports" class="nav-item <?= current_page() === 'rapports' ? 'active' : '' ?>" data-page="rapports">
            <i class="bi bi-file-earmark-bar-graph"></i>
            <span>Rapports</span>
        </a>
        <?php if (in_array($_SESSION['user_role'] ?? '', ['root', 'administrateur'], true)): ?>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 20px;">
        <a href="?page=cours" class="nav-item <?= current_page() === 'cours' ? 'active' : '' ?>" data-page="cours">
            <i class="bi bi-calendar-week"></i>
            <span>Emploi du temps</span>
        </a>
        <a href="?page=finances" class="nav-item <?= current_page() === 'finances' ? 'active' : '' ?>" data-page="finances">
            <i class="bi bi-cash-coin"></i>
            <span>Finances</span>
        </a>
        <a href="?page=jury" class="nav-item <?= current_page() === 'jury' ? 'active' : '' ?>" data-page="jury">
            <i class="bi bi-file-earmark-text"></i>
            <span>Jury</span>
        </a>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 20px;">
        <a href="?page=messages" class="nav-item <?= current_page() === 'messages' ? 'active' : '' ?>" data-page="messages">
            <i class="bi bi-envelope"></i>
            <span>Messages</span>
        </a>
        <a href="?page=utilisateurs" class="nav-item <?= current_page() === 'utilisateurs' ? 'active' : '' ?>" data-page="utilisateurs">
            <i class="bi bi-person-badge"></i>
            <span>Utilisateurs</span>
        </a>
        <a href="?page=parametres" class="nav-item <?= current_page() === 'parametres' ? 'active' : '' ?>" data-page="parametres">
            <i class="bi bi-gear"></i>
            <span>Paramètres</span>
        </a>
        <?php endif; ?>
        <a href="?page=portal" class="nav-item" target="_blank" data-page="portal">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Portail étudiant</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?= h($_SESSION['user_nom'] ?? '') ?></span>
                <span class="user-role"><?= ucfirst($_SESSION['user_role'] ?? '') ?></span>
            </div>
        </div>
        <div class="d-flex gap-1">
            <button id="themeToggle" class="logout-btn" onclick="toggleTheme()" title="Mode sombre" style="font-size:16px;">
                <i class="bi bi-moon-fill"></i>
            </button>
            <a href="?logout=1" class="logout-btn" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>
