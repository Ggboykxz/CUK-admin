<?php
$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT * FROM filieres WHERE active = 1 ORDER BY nom");
$niveaux = db()->fetchAll("SELECT * FROM niveaux WHERE active = 1 ORDER BY ordre");

$statsGenerales = [
    'totalEtudiants' => db()->fetch("SELECT COUNT(*) as c FROM etudiants WHERE annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['c'],
    'tauxReussite' => db()->fetch("SELECT COUNT(*) as c FROM resultats_annuels WHERE annee_academique_id = ? AND decision = 'admis'", [$anneeCourante['id'] ?? 0])['c'],
    'totalAbsences' => db()->fetch("SELECT COUNT(*) as c FROM absences WHERE annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['c'],
    'totalIncidents' => db()->fetch("SELECT COUNT(*) as c FROM incidents")['c']
];

$repartitionFilieres = db()->fetchAll("
    SELECT f.nom, COUNT(e.id) as count 
    FROM filieres f 
    LEFT JOIN etudiants e ON f.id = e.filiere_id AND e.annee_academique_id = ?
    GROUP BY f.id, f.nom
", [$anneeCourante['id'] ?? 0]);

$evolutionNotes = db()->fetchAll("
    SELECT MONTH(n.date_saisie) as mois, AVG(n.moyenne_ec) as moyenne
    FROM notes n
    WHERE n.annee_academique_id = ? AND n.moyenne_ec IS NOT NULL
    GROUP BY mois
    ORDER BY mois
", [$anneeCourante['id'] ?? 0]);
?>

<div class="rapports-page">
    <div class="row mb-4">
        <div class="col-md-12">
            <form class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Année académique</label>
                    <select class="form-select" id="rapportAnnee">
                        <option value="<?= $anneeCourante['id'] ?? '' ?>"><?= htmlspecialchars($anneeCourante['annee'] ?? 'Sélectionner') ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filière</label>
                    <select class="form-select" id="rapportFiliere">
                        <option value="">Toutes les filières</option>
                        <?php foreach ($filieres as $f) : ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Niveau</label>
                    <select class="form-select" id="rapportNiveau">
                        <option value="">Tous</option>
                        <?php foreach ($niveaux as $n) : ?>
                            <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" onclick="genererRapport()">
                        <i class="bi bi-file-earmark-bar-graph"></i> Générer
                    </button>
                    <button type="button" class="btn btn-success ms-2" onclick="exportPDF()">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-info ms-2" onclick="exportExcel()">
                        <i class="bi bi-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['totalEtudiants'] ?></h3>
                    <p>Étudiants</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success"><i class="bi bi-trophy"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['tauxReussite'] ?></h3>
                    <p>Admis</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning"><i class="bi bi-calendar-x"></i></div>
                <div class="stat-info">
                    <h3><?= $statsGenerales['totalAbsences'] ?></h3>
                    <p>Absences</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
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

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-table"></i> Tableau Récapitulatif</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Effectif</th>
                                <th>Moyenne Générale</th>
                                <th>Taux de Réussite</th>
                                <th>Meilleur Moyenne</th>
                            </tr>
                        </thead>
                        <tbody id="recapTable">
                            <?php foreach ($filieres as $f) : ?>
                                <?php foreach ($niveaux as $n) :
                                    $count = db()->fetch("SELECT COUNT(*) as c FROM etudiants WHERE filiere_id = ? AND niveau_id = ? AND annee_academique_id = ?", [$f['id'], $n['id'], $anneeCourante['id'] ?? 0])['c'];
                                    if ($count > 0) :
                                        $avgNote = db()->fetch("SELECT AVG(moyenne_annuelle) as avg FROM resultats_annuels ra JOIN etudiants e ON ra.etudiant_id = e.id WHERE e.filiere_id = ? AND e.niveau_id = ? AND ra.annee_academique_id = ?", [$f['id'], $n['id'], $anneeCourante['id'] ?? 0]);
                                        $bestNote = db()->fetch("SELECT MAX(moyenne_annuelle) as max FROM resultats_annuels ra JOIN etudiants e ON ra.etudiant_id = e.id WHERE e.filiere_id = ? AND e.niveau_id = ? AND ra.annee_academique_id = ?", [$f['id'], $n['id'], $anneeCourante['id'] ?? 0]);
                                        $admis = db()->fetch("SELECT COUNT(*) as c FROM resultats_annuels ra JOIN etudiants e ON ra.etudiant_id = e.id WHERE e.filiere_id = ? AND e.niveau_id = ? AND ra.annee_academique_id = ? AND ra.decision = 'admis'", [$f['id'], $n['id'], $anneeCourante['id'] ?? 0])['c'];
                                        $taux = $count > 0 ? round(($admis / $count) * 100, 1) : 0;
                                        ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['nom']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($n['code']) ?></span></td>
                                    <td><?= $count ?></td>
                                    <td><?= $avgNote['avg'] ? number_format($avgNote['avg'], 2) : '-' ?></td>
                                    <td>
                                        <div class="progress" style="height:20px;">
                                            <div class="progress-bar bg-<?= $taux >= 50 ? 'success' : 'danger' ?>" style="width:<?= $taux ?>%">
                                                <?= $taux ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $bestNote['max'] ? number_format($bestNote['max'], 2) : '-' ?></td>
                                </tr>
                                    <?php endif;
                                endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('chartFilieres'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($repartitionFilieres, 'nom')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($repartitionFilieres, 'count')) ?>,
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
});

function genererRapport() {
    showToast('Génération du rapport...', 'info');
}

function exportPDF() {
    showToast('Export PDF en cours...', 'info');
}

function exportExcel() {
    showToast('Export Excel en cours...', 'info');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:250px';
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
