document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initDataTables();
    updatePageTitle();
});

function initSidebar() {
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
    
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

function initDataTables() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.table').each(function() {
            if (!$(this).closest('.dataTables_wrapper').length) {
                $(this).DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
                    },
                    pageLength: 25,
                    order: [[0, 'asc']]
                });
            }
        });
    }
}

function updatePageTitle() {
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
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
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed animate-fade-in`;
    toast.style.cssText = 'top:80px;right:20px;z-index:9999;min-width:280px;box-shadow:0 4px 12px rgba(0,0,0,0.15)';
    toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('animate-fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('fr-FR');
}

function formatNumber(num, decimals = 2) {
    return parseFloat(num).toFixed(decimals);
}

window.showToast = showToast;
window.formatDate = formatDate;
window.formatNumber = formatNumber;