<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT * FROM filieres WHERE active = 1 ORDER BY nom");

$statsGenerales = [
    'totalEtudiants' => db()->fetch("SELECT COUNT(*) as c FROM etudiants WHERE annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['c'],
    'totalAbsences' => db()->fetch("SELECT COUNT(*) as c FROM absences WHERE annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['c'],
    'totalIncidents' => db()->fetch("SELECT COUNT(*) as c FROM incidents")['c']
];

$repartitionFilieres = db()->fetchAll("SELECT f.nom, COUNT(e.id) as count FROM filieres f LEFT JOIN etudiants e ON f.id = e.filiere_id AND e.annee_academique_id = ? GROUP BY f.id, f.nom", [$anneeCourante['id'] ?? 0]);

$evolutionNotes = db()->fetchAll("SELECT CAST(strftime('%m', n.date_saisie) AS INTEGER) as mois, AVG(n.moyenne_ec) as moyenne FROM notes n WHERE n.annee_academique_id = ? AND n.moyenne_ec IS NOT NULL GROUP BY mois ORDER BY mois", [$anneeCourante['id'] ?? 0]);
?>


<div class="rapports-page">
    <div class="row mb-4">
        <div class="col-md-12">
            <form class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Année académique</label>
                    <select class="form-select" id="rapportAnnee">
                        <option value="<?= $anneeCourante['id'] ?? '' ?>"><?= Security::h($anneeCourante['annee'] ?? 'Sélectionner') ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filière</label>
                    <select class="form-select" id="rapportFiliere">
                        <option value="">Toutes les filières</option>
                        <?php foreach ($filieres as $f) : ?>
                            <option value="<?= $f['id'] ?>"><?= Security::h($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="btnGenererRapport">
                        <i class="bi bi-file-earmark-bar-graph"></i> Générer
                    </button>
                    <button type="button" class="btn btn-success ms-2" id="btnExportPDF">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-info ms-2" id="btnExportExcel">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['totalEtudiants'] ?></h3>
                    <p>Étudiants</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="bi bi-calendar-x"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['totalAbsences'] ?></h3>
                    <p>Absences</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger"><i class="bi bi-shield-exclamation"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['totalIncidents'] ?></h3>
                    <p>Incidents</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-pie-chart"></i> Répartition par Filière</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartFilieres" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-bar-chart"></i> Évolution des Notes</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartNotes" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('chartFilieres'), {
        type: 'doughnut',
        data: {
            labels: <?= Security::safeJson(array_column($repartitionFilieres, 'nom')) ?>,
            datasets: [{
                data: <?= Security::safeJson(array_column($repartitionFilieres, 'count')) ?>,
                backgroundColor: ['#1e3a5f', '#2d5a87', '#4a90a4', '#6bb3c9', '#8fcfe6', '#a5d6a7', '#66bb6a']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'right' } } }
    });

    const moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    const notesData = new Array(12).fill(0);
    <?php foreach ($evolutionNotes as $n) : ?>
    notesData[<?= $n['mois'] - 1 ?>] = <?= $n['moyenne'] ?? 0 ?>;
    <?php endforeach; ?>

    new Chart(document.getElementById('chartNotes'), {
        type: 'line',
        data: {
            labels: moisLabels,
            datasets: [{
                label: 'Moyenne des notes',
                data: notesData,
                borderColor: '#1e3a5f',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(30, 58, 95, 0.1)'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 20 } }
        }
    });

    document.getElementById('btnGenererRapport').addEventListener('click', genererRapport);
    document.getElementById('btnExportPDF').addEventListener('click', exportPDF);
    document.getElementById('btnExportExcel').addEventListener('click', exportExcel);
});

function genererRapport() {
    var filiere = document.getElementById('rapportFiliere').value;
    var url = '../api/export.php?type=liste';
    if (filiere) url += '&filiere_id=' + filiere;
    window.open(url, '_blank');
    showToast('Génération du rapport PDF...', 'info');
}

function exportPDF() {
    var filiere = document.getElementById('rapportFiliere').value;
    var url = '../api/export.php?type=liste';
    if (filiere) url += '&filiere_id=' + filiere;
    window.open(url, '_blank');
    showToast('Export PDF en cours...', 'info');
}

function exportExcel() {
    var filiere = document.getElementById('rapportFiliere').value;
    var url = '../api/export_excel.php?type=etudiants';
    if (filiere) url += '&filiere_id=' + filiere;
    window.open(url, '_blank');
    showToast('Export Excel en cours...', 'info');
}
</script>
