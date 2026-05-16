<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT f.*, i.sigle as institut FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1 ORDER BY i.sigle, f.nom");

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'saveNote') {
        header('Content-Type: application/json');
        $etudiantId = Security::validateInt($_POST['etudiant_id']);
        $ecId = Security::validateInt($_POST['ec_id']);
        $anneeId = Security::validateInt($_POST['annee_academique_id']);
        $cc = $_POST['cc'] !== '' ? floatval($_POST['cc']) : null;
        $tp = $_POST['tp'] !== '' ? floatval($_POST['tp']) : null;
        $examen = $_POST['examen'] !== '' ? floatval($_POST['examen']) : null;

        $ec = db()->fetch("SELECT * FROM ecs WHERE id = ?", [$ecId]);

        $moyenneEc = null;
        if ($cc !== null && $tp !== null && $examen !== null) {
            $totalCoef = $ec['coefficient_cc'] + $ec['coefficient_tp'] + $ec['coefficient_examen'];
            $moyenneEc = $totalCoef > 0 ? round(($cc * $ec['coefficient_cc'] + $tp * $ec['coefficient_tp'] + $examen * $ec['coefficient_examen']) / $totalCoef, 2) : null;
        } elseif ($cc !== null && $examen !== null) {
            $moyenneEc = round($cc * 0.30 + $examen * 0.70, 2);
        } elseif ($examen !== null) {
            $moyenneEc = $examen;
        }

        $existing = db()->fetch("SELECT id FROM notes WHERE etudiant_id = ? AND ec_id = ? AND annee_academique_id = ?", [$etudiantId, $ecId, $anneeId]);

        $data = [
            'etudiant_id' => $etudiantId,
            'ec_id' => $ecId,
            'annee_academique_id' => $anneeId,
            'cc' => $cc,
            'tp' => $tp,
            'examen' => $examen,
            'moyenne_ec' => $moyenneEc,
            'saisi_par' => $_SESSION['user_id']
        ];

        if ($existing) {
            db()->update('notes', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            db()->insert('notes', $data);
        }

        echo json_encode(['success' => true, 'moyenne' => $moyenneEc]);
        exit;
    }

    if ($action === 'calculerSemestre') {
        header('Content-Type: application/json');
        $etudiantId = Security::validateInt($_POST['etudiant_id']);
        $anneeId = Security::validateInt($_POST['annee_academique_id']);
        $semestre = Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1');
        $filiereId = Security::validateInt($_POST['filiere_id']);

        $semestreData = db()->fetch("SELECT * FROM semestres WHERE code LIKE ? AND filiere_id = ?", [$filiereId . '-' . $semestre . '%', $filiereId]);

        if (!$semestreData) {
            echo json_encode(['success' => false, 'error' => 'Semestre non trouvé']);
            exit;
        }

        $ecs = db()->fetchAll(
            "SELECT ec.id as ec_id, ec.code as ec_code, ec.nom as ec_nom, ec.coefficient,
                    ue.id as ue_id, ue.code as ue_code, ue.nom as ue_nom, ue.credits as ue_credits,
                    n.moyenne_ec
             FROM ues ue
             JOIN ecs ec ON ec.ue_id = ue.id AND ec.active = 1
             LEFT JOIN notes n ON n.ec_id = ec.id AND n.etudiant_id = ? AND n.annee_academique_id = ?
             WHERE ue.semestre_id = ? AND ue.active = 1
             ORDER BY ue.code, ec.code",
            [$etudiantId, $anneeId, $semestreData['id']]
        );

        $resultats = [];
        $uesGrouped = [];
        foreach ($ecs as $ec) {
            $uesGrouped[$ec['ue_id']]['ue'] = [
                'id' => $ec['ue_id'],
                'code' => $ec['ue_code'],
                'nom' => $ec['ue_nom'],
                'credits' => $ec['ue_credits'],
            ];
            $uesGrouped[$ec['ue_id']]['ecs'][] = $ec;
        }

        $totalMoyenne = 0;
        $totalCreditsUE = 0;

        foreach ($uesGrouped as $groupId => $group) {
            $ue = $group['ue'];
            $ecList = $group['ecs'];
            $moyenneUe = 0;
            $totalCoefUe = 0;

            foreach ($ecList as $ec) {
                if ($ec['moyenne_ec'] !== null) {
                    $moyenneUe += floatval($ec['moyenne_ec']) * floatval($ec['coefficient']);
                    $totalCoefUe += floatval($ec['coefficient']);
                }
            }

            $moyenneUeFinale = $totalCoefUe > 0 ? round($moyenneUe / $totalCoefUe, 2) : null;
            $valid = $moyenneUeFinale !== null && $moyenneUeFinale >= 10;

            if ($moyenneUeFinale !== null) {
                $totalMoyenne += $moyenneUe;
                $totalCreditsUE += floatval($ue['credits']);
            }

            $resultats[] = [
                'ue' => $ue,
                'moyenne' => $moyenneUeFinale,
                'credits' => floatval($ue['credits']),
                'credits_obtenus' => $valid ? floatval($ue['credits']) : 0,
                'valide' => $valid
            ];
        }

        $moyenneSemestre = $totalCreditsUE > 0 ? round($totalMoyenne / $totalCreditsUE, 2) : null;
        $mention = getMention($moyenneSemestre);
        $decision = getDecision($moyenneSemestre, $moyenneSemestre !== null);
        $creditsObtenus = array_sum(array_column($resultats, 'credits_obtenus'));

        if (!empty($_POST['save'])) {
            $existing = db()->fetch(
                "SELECT id FROM moyennes_semestrielles WHERE etudiant_id = ? AND semestre_id = ? AND annee_academique_id = ?",
                [$etudiantId, $semestreData['id'], $anneeId]
            );
            $validation = $moyenneSemestre !== null && $moyenneSemestre >= 10 ? 'valide' : 'ajourne';
            $data = [
                'etudiant_id' => $etudiantId,
                'semestre_id' => $semestreData['id'],
                'annee_academique_id' => $anneeId,
                'moyenne_semestre' => $moyenneSemestre,
                'credits_obtenus' => $creditsObtenus,
                'total_credits' => $totalCreditsUE,
                'validation' => $validation,
                'mention_semestre' => $mention
            ];
            if ($existing) {
                db()->update('moyennes_semestrielles', $data, 'id = :id', ['id' => $existing['id']]);
            } else {
                db()->insert('moyennes_semestrielles', $data);
            }
        }

        echo json_encode([
            'success' => true,
            'saved' => !empty($_POST['save']),
            'resultats' => $resultats,
            'moyenne_semestre' => $moyenneSemestre,
            'total_credits' => $totalCreditsUE,
            'credits_obtenus' => $creditsObtenus,
            'mention' => $mention,
            'decision' => $decision
        ]);
        exit;
    }

    if ($action === 'get_ecs') {
        header('Content-Type: application/json');
        $etudiantId = Security::validateInt($_GET['etudiant_id']);
        $semestre = Security::validateEnum($_GET['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1');
        $filiereId = Security::validateInt($_GET['filiere_id']);

        $semesterData = db()->fetch("SELECT * FROM semestres WHERE code LIKE ? AND filiere_id = ?", [$filiereId . '-' . $semestre . '%', $filiereId]);
        if (!$semesterData) {
            echo json_encode([]);
            exit;
        }
        $ecs = db()->fetchAll("SELECT ec.*, ue.nom as ue_nom FROM ecs ec JOIN ues ue ON ec.ue_id = ue.id WHERE ue.semestre_id = ? AND ec.active = 1 AND ue.active = 1 ORDER BY ue.code, ec.code", [$semesterData['id']]);
        echo Security::safeJson($ecs);
        exit;
    }

    if ($action === 'get_note') {
        header('Content-Type: application/json');
        $etudiantId = Security::validateInt($_GET['etudiant_id']);
        $ecId = Security::validateInt($_GET['ec_id']);
        $anneeId = Security::validateInt($_GET['annee_id']);
        $note = db()->fetch("SELECT * FROM notes WHERE etudiant_id = ? AND ec_id = ? AND annee_academique_id = ?", [$etudiantId, $ecId, $anneeId]);
        echo Security::safeJson($note ?: []);
        exit;
    }
}

function getMention($moyenne)
{
    if ($moyenne === null) return '-';
    if ($moyenne >= 18) return 'Excellent';
    if ($moyenne >= 16) return 'Très Bien';
    if ($moyenne >= 14) return 'Bien';
    if ($moyenne >= 12) return 'Assez Bien';
    if ($moyenne >= 10) return 'Passable';
    return 'Ajourné';
}

function getDecision($moyenne, $hasNotes)
{
    if (!$hasNotes || $moyenne === null) return 'en_attente';
    return $moyenne >= 10 ? 'admis' : 'ajourne';
}

$etudiants = db()->fetchAll("SELECT e.id, e.numero, e.nom, e.prenom, f.nom as filiere, f.id as filiere_id, e.semestre FROM etudiants e JOIN filieres f ON e.filiere_id = f.id WHERE e.annee_academique_id = ? AND e.statut = 'actif' ORDER BY f.nom, e.nom", [$anneeCourante['id'] ?? 0]);
?>

<div class="notes-page">
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-mortarboard"></i> Saisie des Notes DUT</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Étudiant</label>
                    <select class="form-select" id="etudiantSelect" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($etudiants as $e) : ?>
                            <option value="<?= $e['id'] ?>" data-filiere="<?= $e['filiere_id'] ?>" data-semestre="<?= $e['semestre'] ?>">
                                <?= Security::h($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Semestre</label>
                    <select class="form-select" id="semestreSelect">
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                        <option value="S4">S4</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">UE / Matière</label>
                    <select class="form-select" id="ecSelect" disabled>
                        <option value="">Sélectionner d'abord un étudiant</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" id="btnLoadEC">
                        <i class="bi bi-search"></i> Charger
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="notesFormCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-pencil-square"></i> Saisie des Notes</h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-info btn-sm" id="btnCalculerSemestre">
                    <i class="bi bi-calculator"></i> Calculer Semestre
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" id="btnSaveMoyenne" style="display:none;">
                    <i class="bi bi-save"></i> Sauvegarder moyenne
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnSaisieGroupee">
                    <i class="bi bi-table"></i> Saisie groupée
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="noteForm">
                <input type="hidden" id="noteEtudiantId">
                <input type="hidden" id="noteEcId">
                <input type="hidden" id="noteFiliereId">
                <input type="hidden" id="noteAnneeId" value="<?= $anneeCourante['id'] ?? '' ?>">
                <input type="hidden" id="csrfToken" value="<?= Security::generateCsrfToken() ?>">
                
                <div class="row g-3 mb-4 bg-light p-3 rounded">
                    <div class="col-md-3">
                        <label class="form-label">UE</label>
                        <input type="text" class="form-control" id="noteUe" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Matière (EC)</label>
                        <input type="text" class="form-control" id="noteEc" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Coef.</label>
                        <input type="text" class="form-control" id="noteCoef" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Moyenne actuelle</label>
                        <input type="text" class="form-control bg-success text-white" id="noteMoyenneActuelle" readonly value="-">
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Contrôle Continu (CC)</label>
                        <input type="number" class="form-control" id="noteCC" min="0" max="20" step="0.25" placeholder="0-20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Travaux Pratiques (TP)</label>
                        <input type="number" class="form-control" id="noteTP" min="0" max="20" step="0.25" placeholder="0-20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Examen</label>
                        <input type="number" class="form-control" id="noteExamen" min="0" max="20" step="0.25" placeholder="0-20">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" id="btnSaveNote">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4" id="moyennesCard" style="display:none;">
        <div class="card-header">
            <h5><i class="bi bi-table"></i> Résultats du Semestre</h5>
        </div>
        <div class="card-body">
            <table class="table" id="moyennesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>UE</th>
                        <th>Crédits</th>
                        <th>Moyenne</th>
                        <th>Validation</th>
                    </tr>
                </thead>
                <tbody id="moyennesBody"></tbody>
            </table>
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="alert alert-info">
                        <strong>Moyenne Semestre:</strong> <span id="moyenneSemestre">-</span>/20
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-success">
                        <strong>Crédits Obtenus:</strong> <span id="creditsObtenus">-</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-warning">
                        <strong>Mention:</strong> <span id="mention">-</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-secondary">
                        <strong>Décision:</strong> <span id="decision">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
const ecsCache = {};

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('etudiantSelect').addEventListener('change', function() {
        document.getElementById('ecSelect').disabled = this.value === '';
        const option = this.options[this.selectedIndex];
        if (option.dataset.semestre) {
            document.getElementById('semestreSelect').value = option.dataset.semestre;
        }
    });

    document.getElementById('btnLoadEC').addEventListener('click', loadEC);
    document.getElementById('btnCalculerSemestre')?.addEventListener('click', calculerSemestre);
    document.getElementById('btnSaveMoyenne')?.addEventListener('click', sauvegarderMoyenne);
    document.getElementById('btnSaisieGroupee')?.addEventListener('click', ouvrirSaisieGroupee);
    document.getElementById('btnSaveNote').addEventListener('click', saveNote);
});

function loadEC() {
    const etudiantId = document.getElementById('etudiantSelect').value;
    const semestre = document.getElementById('semestreSelect').value;
    const option = document.getElementById('etudiantSelect').options[document.getElementById('etudiantSelect').selectedIndex];
    const filiereId = option.dataset.filiere;
    
    if (!etudiantId) return;
    
    document.getElementById('noteFiliereId').value = filiereId;
    
    const select = document.getElementById('ecSelect');
    select.innerHTML = '<option value="">Chargement...</option>';
    select.disabled = false;
    
    fetch(`?page=notes&action=get_ecs&etudiant_id=${etudiantId}&semestre=${semestre}&filiere_id=${filiereId}`)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Sélectionner --</option>';
            data.forEach(ec => {
                const opt = document.createElement('option');
                opt.value = ec.id;
                opt.dataset.ue = ec.ue_nom || '';
                opt.dataset.coef = ec.coefficient || '';
                opt.textContent = ec.code + ' - ' + ec.nom;
                select.appendChild(opt);
            });
            ecsCache[etudiantId + '_' + semestre] = data;
            document.getElementById('notesFormCard').style.display = 'block';
        });
}

document.getElementById('ecSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (this.value) {
        document.getElementById('noteEtudiantId').value = document.getElementById('etudiantSelect').value;
        document.getElementById('noteEcId').value = this.value;
        document.getElementById('noteUe').value = selected.dataset.ue || '';
        document.getElementById('noteCoef').value = selected.dataset.coef || '';
        document.getElementById('noteEc').value = selected.textContent;
        
        loadNoteExistante();
    }
});

function loadNoteExistante() {
    const etudiantId = document.getElementById('noteEtudiantId').value;
    const ecId = document.getElementById('noteEcId').value;
    const anneeId = document.getElementById('noteAnneeId').value;
    
    fetch(`?page=notes&action=get_note&etudiant_id=${etudiantId}&ec_id=${ecId}&annee_id=${anneeId}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('noteCC').value = data.cc || '';
            document.getElementById('noteTP').value = data.tp || '';
            document.getElementById('noteExamen').value = data.examen || '';
            document.getElementById('noteMoyenneActuelle').value = data.moyenne_ec || '-';
        });
}

function saveNote() {
    const data = {
        action: 'saveNote',
        _csrf_token: document.getElementById('csrfToken').value,
        etudiant_id: document.getElementById('noteEtudiantId').value,
        ec_id: document.getElementById('noteEcId').value,
        annee_academique_id: document.getElementById('noteAnneeId').value,
        cc: document.getElementById('noteCC').value,
        tp: document.getElementById('noteTP').value,
        examen: document.getElementById('noteExamen').value
    };
    
    fetch('?page=notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            document.getElementById('noteMoyenneActuelle').value = result.moyenne || '-';
            showToast('Note enregistrée: ' + result.moyenne, 'success');
        }
    });
}

function calculerSemestre() {
    var etudiantId = document.getElementById('etudiantSelect').value;
    fetch('?page=notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'calculerSemestre',
            _csrf_token: document.getElementById('csrfToken').value,
            etudiant_id: etudiantId,
            semestre: document.getElementById('semestreSelect').value,
            filiere_id: document.getElementById('noteFiliereId').value,
            annee_academique_id: document.getElementById('noteAnneeId').value
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            var tbody = document.getElementById('moyennesBody');
            tbody.innerHTML = '';

            result.resultats.forEach(function(r) {
                var tr = document.createElement('tr');
                var badge = r.moyenne
                    ? '<span class="badge bg-' + (r.valide ? 'success' : 'danger') + '">' + (r.valide ? 'Validé ✓' : 'Ajourné ✗') + '</span>'
                    : '-';
                tr.innerHTML = '<td>' + r.ue.code + '</td><td>' + r.ue.nom + '</td><td>' + r.credits + '</td><td><strong>' + (r.moyenne ?? '-') + '/20</strong></td><td>' + badge + '</td>';
                tbody.appendChild(tr);
            });

            document.getElementById('moyenneSemestre').textContent = result.moyenne_semestre ?? '-';
            document.getElementById('creditsObtenus').textContent = result.credits_obtenus ?? 0;
            document.getElementById('mention').textContent = result.mention;
            document.getElementById('decision').textContent = result.decision;

            document.getElementById('moyennesCard').style.display = 'block';
            document.getElementById('btnSaveMoyenne').style.display = 'inline-block';
        }
    });
}

function sauvegarderMoyenne() {
    fetch('?page=notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'calculerSemestre',
            _csrf_token: document.getElementById('csrfToken').value,
            etudiant_id: document.getElementById('etudiantSelect').value,
            semestre: document.getElementById('semestreSelect').value,
            filiere_id: document.getElementById('noteFiliereId').value,
            annee_academique_id: document.getElementById('noteAnneeId').value,
            save: '1'
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success && result.saved) {
            showToast('Moyenne semestrielle sauvegardée', 'success');
        } else {
            showToast('Erreur lors de la sauvegarde', 'error');
        }
    });
}

function ouvrirSaisieGroupee() {
    var filiereId = document.getElementById('noteFiliereId').value;
    var etudiantId = document.getElementById('etudiantSelect').value;
    var anneeId = document.getElementById('noteAnneeId').value;
    var semestre = document.getElementById('semestreSelect').value;

    if (!filiereId || !etudiantId) {
        showToast('Sélectionnez d\'abord un étudiant', 'warning');
        return;
    }

    fetch('?page=notes&action=get_ecs&etudiant_id=' + etudiantId + '&semestre=' + semestre + '&filiere_id=' + filiereId)
        .then(function(r) { return r.json(); })
        .then(function(ecs) {
            if (ecs.length === 0) {
                showToast('Aucun EC trouvé pour ce semestre', 'warning');
                return;
            }
            var html = '<div style="max-height:500px;overflow-y:auto;"><table class="table table-sm" id="bulkTable">' +
                '<thead><tr><th>UE</th><th>EC</th><th>Coef</th><th>CC (0-20)</th><th>TP (0-20)</th><th>Examen (0-20)</th><th>Valider</th></tr></thead><tbody>';
            ecs.forEach(function(ec) {
                html += '<tr>' +
                    '<td>' + escapeHtml(ec.ue_nom || '') + '</td>' +
                    '<td><strong>' + escapeHtml(ec.code) + '</strong> - ' + escapeHtml(ec.nom) + '</td>' +
                    '<td>' + (ec.coefficient || 1) + '</td>' +
                    '<td><input type="number" class="form-control form-control-sm bulk-cc" data-ec="' + ec.id + '" min="0" max="20" step="0.25" style="width:80px;"></td>' +
                    '<td><input type="number" class="form-control form-control-sm bulk-tp" data-ec="' + ec.id + '" min="0" max="20" step="0.25" style="width:80px;"></td>' +
                    '<td><input type="number" class="form-control form-control-sm bulk-examen" data-ec="' + ec.id + '" min="0" max="20" step="0.25" style="width:80px;"></td>' +
                    '<td><input type="checkbox" class="form-check-input bulk-validate" data-ec="' + ec.id + '"></td>' +
                    '</tr>';
            });
            html += '</tbody></table></div>' +
                '<div class="text-end mt-3">' +
                '<button class="btn btn-primary" id="btnBulkSave"><i class="bi bi-save"></i> Tout enregistrer</button>' +
                '</div>';

            var modal = document.getElementById('modal');
            modal.querySelector('.modal-title').textContent = 'Saisie groupée des notes';
            modal.querySelector('.modal-body').innerHTML = html;
            modal.querySelector('.modal-footer').innerHTML = '';
            modal.querySelector('.modal-body').querySelector('#btnBulkSave').addEventListener('click', sauvegarderBulk);
            new bootstrap.Modal(modal).show();
        });
}

function sauvegarderBulk() {
    var rows = document.querySelectorAll('#bulkTable tbody tr');
    var promises = [];
    var csrfToken = document.getElementById('csrfToken').value;
    var etudiantId = document.getElementById('noteEtudiantId').value;
    var anneeId = document.getElementById('noteAnneeId').value;

    rows.forEach(function(row) {
        var cc = row.querySelector('.bulk-cc');
        var tp = row.querySelector('.bulk-tp');
        var examen = row.querySelector('.bulk-examen');
        var validate = row.querySelector('.bulk-validate');

        var ecId = (cc ? cc.dataset.ec : null);
        if (!ecId) return;

        var data = new URLSearchParams({
            action: 'saveNote',
            _csrf_token: csrfToken,
            etudiant_id: etudiantId,
            ec_id: ecId,
            annee_academique_id: anneeId,
            cc: cc ? cc.value : '',
            tp: tp ? tp.value : '',
            examen: examen ? examen.value : ''
        });

        promises.push(fetch('?page=notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data
        }).then(function(r) { return r.json(); }));
    });

    Promise.all(promises).then(function(results) {
        var ok = results.filter(function(r) { return r.success; }).length;
        showToast(ok + '/' + results.length + ' notes enregistrées', ok === results.length ? 'success' : 'warning');
        bootstrap.Modal.getInstance(document.getElementById('modal')).hide();
    });
}
</script>
