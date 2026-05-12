document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initMobileMenu();
    updatePageTitle();
});

function initSidebar() {
    var toggleBtn = document.querySelector('.sidebar-toggle');
    var sidebar = document.querySelector('.sidebar');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
        });
    }

    var navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    navItems.forEach(function (item) {
        item.addEventListener('click', function () {
            navItems.forEach(function (i) { i.classList.remove('active'); });
            this.classList.add('active');
            closeSidebarMobile();
        });
    });
}

function initMobileMenu() {
    var menuBtn = document.getElementById('mobileMenuBtn');
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');

    if (!menuBtn && document.querySelector('.sidebar')) {
        var topBarLeft = document.querySelector('.top-bar-left');
        if (topBarLeft) {
            var btn = document.createElement('button');
            btn.id = 'mobileMenuBtn';
            btn.className = 'mobile-menu-btn';
            btn.innerHTML = '<i class="bi bi-list"></i>';
            btn.setAttribute('aria-label', 'Menu');
            topBarLeft.prepend(btn);
            menuBtn = btn;
        }
    }

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }
}

function closeSidebarMobile() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
}

function updatePageTitle() {
    var params = new URLSearchParams(window.location.search);
    var currentPage = params.get('page') || 'dashboard';
    var titles = {
        dashboard: 'Tableau de bord',
        etudiants: 'Étudiants',
        notes: 'Notes',
        absences: 'Absences',
        filieres: 'Filières & UE',
        disciplinarite: 'Disciplinarité',
        orientations: 'Orientations',
        rapports: 'Rapports',
        utilisateurs: 'Utilisateurs',
        parametres: 'Paramètres'
    };
    var titleEl = document.querySelector('.page-title');
    if (titleEl && titles[currentPage]) {
        titleEl.textContent = titles[currentPage];
    }
}

function showToast(message, type) {
    if (typeof type === 'undefined') type = 'info';
    var toast = document.createElement('div');
    var iconMap = { success: 'check-circle', error: 'x-circle', danger: 'x-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    var icon = iconMap[type] || 'info-circle';
    toast.className = 'alert alert-' + type + ' position-fixed animate-fade-in';
    toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:280px;box-shadow:0 4px 16px rgba(0,0,0,0.15);border:none;border-radius:10px;padding:12px 20px';
    toast.innerHTML = '<i class="bi bi-' + icon + ' me-2"></i>' + message.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    document.body.appendChild(toast);
    setTimeout(function () {
        toast.classList.add('animate-fade-out');
        setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
}

function formatDate(date) {
    if (!date) return '';
    var d = new Date(date);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}

function formatNumber(num, decimals) {
    if (typeof decimals === 'undefined') decimals = 2;
    return parseFloat(num || 0).toFixed(decimals);
}

// ===== DARK MODE =====
(function() {
    var theme = localStorage.getItem('cuk-theme') || 'light';
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }

    window.toggleTheme = function() {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('cuk-theme', next);
        var btn = document.getElementById('themeToggle');
        if (btn) {
            btn.innerHTML = next === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
        }
    };
})();

window.showToast = showToast;
window.formatDate = formatDate;
window.formatNumber = formatNumber;
