<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$etudiants = db()->fetchAll("SELECT id, numero, nom, prenom FROM etudiants WHERE annee_academique_id = ? ORDER BY nom", [$anneeCourante['id'] ?? 0]);

if (isset($_POST['action'])) {
    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Session expirée';
        header('Location: ?page=absences');
        exit;
    }

    if ($_POST['action'] === 'ajouter') {
        $data = [
            'etudiant_id' => Security::validateInt($_POST['etudiant_id']),
            'ec_id' => !empty($_POST['ec_id']) ? Security::validateInt($_POST['ec_id']) : null,
            'annee_academique_id' => Security::validateInt($_POST['annee_academique_id']),
            'date_absence' => Security::validateDate($_POST['date_absence'] ?? ''),
            'nombre_heures' => Security::validateInt($_POST['nombre_heures'] ?? 2, 2),
            'justifiee' => isset($_POST['justifiee']) ? 1 : 0,
            'motif' => trim($_POST['motif'] ?? ''),
            'saisi_par' => $_SESSION['user_id']
        ];

        db()->insert('absences', $data);

        Security::logActivity('ajouter_absence', "Absence ajoutée", 'absences');

        $_SESSION['success'] = 'Absence enregistrée avec succès';
        header('Location: ?page=absences');
        exit;
    }

    if ($_POST['action'] === 'justifier') {
        db()->update('absences', [
            'justifiee' => 1,
            'motif' => trim($_POST['motif'] ?? '')
        ], 'id = :id', ['id' => Security::validateInt($_POST['id'])]);

        $_SESSION['success'] = 'Absence justifiée';
        header('Location: ?page=absences');
        exit;
    }

    if ($_POST['action'] === 'supprimer') {
        db()->delete('absences', 'id = :id', ['id' => Security::validateInt($_POST['id'])]);
        $_SESSION['success'] = 'Absence supprimée';
        header('Location: ?page=absences');
        exit;
    }
}

Security::showSuccess();
Security::showError();

$absences = db()->fetchAll("SELECT a.*, e.numero, e.nom, e.prenom, f.nom as filiere, ec.nom as matiere FROM absences a JOIN etudiants e ON a.etudiant_id = e.id JOIN filieres f ON e.filiere_id = f.id LEFT JOIN ecs ec ON a.ec_id = ec.id WHERE a.annee_academique_id = ? ORDER BY a.date_absence DESC", [$anneeCourante['id'] ?? 0]);

$statsAbsences = [
    'total' => count($absences),
    'justifiees' => count(array_filter($absences, fn($a) => $a['justifiee'])),
    'nonJustifiees' => count(array_filter($absences, fn($a) => !$a['justifiee']))
];
?>

<div class="absences-page">
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#absencesList" type="button"><i class="bi bi-list"></i> Absences</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#appelTab" type="button"><i class="bi bi-check2-square"></i> Appel</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="absencesList">
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-calendar-x"></i></div>
            <div class="stat-info">
                <h3><?= $statsAbsences['total'] ?></h3>
                <p>Total Absences</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <h3><?= $statsAbsences['justifiees'] ?></h3>
                <p>Justifiées</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-info">
                <h3><?= $statsAbsences['nonJustifiees'] ?></h3>
                <p>Non Justifiées</p>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-plus-circle"></i> Nouvelle Absence</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="annee_academique_id" value="<?= $anneeCourante['id'] ?? '' ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Étudiant *</label>
                    <select class="form-select" name="etudiant_id" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($etudiants as $e) : ?>
                            <option value="<?= $e['id'] ?>"><?= Security::h($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date *</label>
                    <input type="date" class="form-control" name="date_absence" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Heures</label>
                    <input type="number" class="form-control" name="nombre_heures" value="2" min="1" max="8">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Motif</label>
                    <input type="text" class="form-control" name="motif" placeholder="Ex: Maladie">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="justifiee" name="justifiee">
                        <label class="form-check-label" for="justifiee">Absence justifiée</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-list"></i> Liste des Absences</h5>
        </div>
        <div class="card-body">
            <table id="absencesTable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Étudiant</th>
                        <th>Filière</th>
                        <th>Heures</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($absences as $a) : ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                        <td>
                            <strong><?= Security::h($a['prenom'] . ' ' . $a['nom']) ?></strong>
                            <small class="d-block text-muted"><?= Security::h($a['numero']) ?></small>
                        </td>
                        <td><?= Security::h($a['filiere'] ?? '') ?></td>
                        <td><?= $a['nombre_heures'] ?>h</td>
                        <td>
                            <?php if ($a['justifiee']) : ?>
                                <span class="badge bg-success">Justifiée</span>
                                <?php if ($a['motif']) : ?>
                                    <small class="d-block text-muted"><?= Security::h($a['motif']) ?></small>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="badge bg-danger">Non justifiée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if (!$a['justifiee']) : ?>
                                <button class="btn btn-outline-success" onclick="justifierAbsence(<?= $a['id'] ?>)" title="Justifier">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-outline-danger" onclick="supprimerAbsence(<?= $a['id'] ?>)" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
            </div><!-- end absencesList -->

        <div class="tab-pane fade" id="appelTab">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-check2-square"></i> Appel des étudiants</h5></div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Filière *</label>
                            <select class="form-select" id="appelFiliere">
                                <option value="">Sélectionner...</option>
                                <?php
                                $filieres = db()->fetchAll("SELECT id, nom FROM filieres WHERE active = 1 ORDER BY nom");
                                foreach ($filieres as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= h($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semestre *</label>
                            <select class="form-select" id="appelSemestre">
                                <option value="S1">S1</option>
                                <option value="S2">S2</option>
                                <option value="S3">S3</option>
                                <option value="S4">S4</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" id="appelDate" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary" onclick="chargerAppel()">
                                <i class="bi bi-search"></i> Charger
                            </button>
                        </div>
                    </div>
                    <div id="appelContent" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-hover" id="appelTable">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">Présent</th>
                                        <th>N°</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                    </tr>
                                </thead>
                                <tbody id="appelBody"></tbody>
                            </table>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success" onclick="toutPresence(true)"><i class="bi bi-check-all"></i> Tous présents</button>
                                    <button class="btn btn-danger" onclick="toutPresence(false)"><i class="bi bi-x"></i> Tous absents</button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-primary" onclick="sauvegarderAppel()">
                                    <i class="bi bi-save"></i> Enregistrer l'appel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end appelTab -->
    </div>
</div>

<div class="modal fade" id="justifierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Justifier l'absence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="justifier">
                    <input type="hidden" name="id" id="justifierId">
                    <div class="mb-3">
                        <label class="form-label">Motif de justification</label>
                        <textarea class="form-control" name="motif" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="supprimerForm" method="POST" style="display:none;">
    <?= Security::csrfField() ?>
    <input type="hidden" name="action" value="supprimer">
    <input type="hidden" name="id" id="supprimerId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#absencesTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
        pageLength: 25,
        order: [[0, 'desc']]
    });
});

function justifierAbsence(id) {
    document.getElementById('justifierId').value = id;
    new bootstrap.Modal(document.getElementById('justifierModal')).show();
}

function supprimerAbsence(id) {
    if (confirm('Supprimer cette absence ?')) {
        document.getElementById('supprimerId').value = id;
        document.getElementById('supprimerForm').submit();
    }
}

// === APPEL ===
var appelData = [];

function chargerAppel() {
    var filiereId = document.getElementById('appelFiliere').value;
    var semestre = document.getElementById('appelSemestre').value;

    if (!filiereId) { showToast('Veuillez sélectionner une filière', 'warning'); return; }

    fetch('../api/appel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_classe',
            _csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '',
            filiere_id: filiereId,
            semestre: semestre
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            appelData = data.etudiants;
            var tbody = document.getElementById('appelBody');
            tbody.innerHTML = '';
            data.etudiants.forEach(function(e) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><input type="checkbox" class="form-check-input appel-check" data-id="' + e.id + '" checked></td>' +
                    '<td>' + escapeHtml(e.numero) + '</td>' +
                    '<td>' + escapeHtml(e.nom) + '</td>' +
                    '<td>' + escapeHtml(e.prenom) + '</td>';
                tbody.appendChild(tr);
            });
            document.getElementById('appelContent').style.display = 'block';
            showToast(data.etudiants.length + ' étudiants chargés', 'info');
        }
    });
}

function toutPresence(present) {
    var checks = document.querySelectorAll('.appel-check');
    checks.forEach(function(c) { c.checked = present; });
}

function sauvegarderAppel() {
    var filiereId = document.getElementById('appelFiliere').value;
    var semestre = document.getElementById('appelSemestre').value;
    var date = document.getElementById('appelDate').value;
    var checks = document.querySelectorAll('.appel-check');
    var presences = [];

    checks.forEach(function(c) {
        presences.push({
            id: parseInt(c.dataset.id),
            present: c.checked
        });
    });

    fetch('../api/appel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'save_appel',
            _csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '',
            filiere_id: filiereId,
            semestre: semestre,
            date_appel: date,
            presences: JSON.stringify(presences)
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Appel enregistré: ' + data.absences_enregistrees + ' absence(s)', 'success');
        } else {
            showToast(data.error || 'Erreur', 'error');
        }
    });
}
</script>
