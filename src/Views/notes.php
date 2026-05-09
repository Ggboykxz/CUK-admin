<?php
$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT f.*, i.sigle as institut FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1 ORDER BY i.sigle, f.nom");

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'saveNote') {
        header('Content-Type: application/json');
        $etudiantId = intval($_POST['etudiant_id']);
        $ecId = intval($_POST['ec_id']);
        $anneeId = intval($_POST['annee_academique_id']);
        $cc = $_POST['cc'] !== '' ? floatval($_POST['cc']) : null;
        $tp = $_POST['tp'] !== '' ? floatval($_POST['tp']) : null;
        $examen = $_POST['examen'] !== '' ? floatval($_POST['examen']) : null;

        $ec = db()->fetch("SELECT * FROM ecs WHERE id = ?", [$ecId]);

        $moyenneEc = null;
        if ($cc !== null && $tp !== null && $examen !== null) {
            $totalCoef = $ec['coefficient_cc'] + $ec['coefficient_tp'] + $ec['coefficient_examen'];
            $moyenneEc = round(($cc * $ec['coefficient_cc'] + $tp * $ec['coefficient_tp'] + $examen * $ec['coefficient_examen']) / $totalCoef, 2);
        } elseif ($cc !== null && $examen !== null) {
            $moyenneEc = round($cc * 0.30 + $examen * 0.70, 2);
        } elseif ($examen !== null) {
            $moyenneEc = $examen;
        }

        $existing = db()->fetch(
            "SELECT id FROM notes WHERE etudiant_id = ? AND ec_id = ? AND annee_academique_id = ?",
            [$etudiantId, $ecId, $anneeId]
        );

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
        $etudiantId = intval($_POST['etudiant_id']);
        $anneeId = intval($_POST['annee_academique_id']);
        $semestre = $_POST['semestre'];
        $filiereId = intval($_POST['filiere_id']);

        $semestreData = db()->fetch("SELECT * FROM semestres WHERE code LIKE ? AND filiere_id = ?", [$filiereId . '-' . $semestre . '%', $filiereId]);

        if (!$semestreData) {
            echo json_encode(['success' => false, 'error' => 'Semestre non trouvé']);
            exit;
        }

        $ues = db()->fetchAll("SELECT * FROM ues WHERE semestre_id = ? AND active = 1", [$semestreData['id']]);
        $resultats = [];
        $totalMoyenne = 0;
        $totalCredits = 0;
        $totalCreditsUE = 0;

        foreach ($ues as $ue) {
            $ecs = db()->fetchAll("SELECT * FROM ecs WHERE ue_id = ? AND active = 1", [$ue['id']]);
            $moyenneUe = 0;
            $totalCoefUe = 0;
            $creditsUe = 0;

            foreach ($ecs as $ec) {
                $note = db()->fetch(
                    "SELECT * FROM notes WHERE etudiant_id = ? AND ec_id = ? AND annee_academique_id = ?",
                    [$etudiantId, $ec['id'], $anneeId]
                );

                if ($note && $note['moyenne_ec'] !== null) {
                    $moyenneUe += floatval($note['moyenne_ec']) * floatval($ec['coefficient']);
                    $totalCoefUe += floatval($ec['coefficient']);
                    $creditsUe += floatval($ec['coefficient']);
                }
            }

            $moyenneUeFinale = $totalCoefUe > 0 ? round($moyenneUe / $totalCoefUe, 2) : null;
            $valid = $moyenneUeFinale !== null && $moyenneUeFinale >= 10;

            if ($moyenneUeFinale !== null) {
                $totalMoyenne += $moyenneUe;
                $totalCredits += $creditsUe;
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

        echo json_encode([
            'success' => true,
            'resultats' => $resultats,
            'moyenne_semestre' => $moyenneSemestre,
            'total_credits' => $totalCreditsUE,
            'credits_obtenus' => array_sum(array_column($resultats, 'credits_obtenus')),
            'mention' => $mention,
            'decision' => $decision
        ]);
        exit;
    }
}

function getMention($moyenne)
{
    if ($moyenne === null) {
        return '-';
    }
    if ($moyenne >= 18) {
        return 'Excellent';
    }
    if ($moyenne >= 16) {
        return 'Très Bien';
    }
    if ($moyenne >= 14) {
        return 'Bien';
    }
    if ($moyenne >= 12) {
        return 'Assez Bien';
    }
    if ($moyenne >= 10) {
        return 'Passable';
    }
    return 'Ajourné';
}

function getDecision($moyenne, $hasNotes)
{
    if (!$hasNotes || $moyenne === null) {
        return 'en_attente';
    }
    if ($moyenne >= 10) {
        return 'admis';
    }
    return 'ajourne';
}

$etudiants = db()->fetchAll("
    SELECT e.id, e.numero, e.nom, e.prenom, f.nom as filiere, f.id as filiere_id, e.semestre
    FROM etudiants e
    JOIN filieres f ON e.filiere_id = f.id
    WHERE e.annee_academique_id = ? AND e.statut = 'actif'
    ORDER BY f.nom, e.nom
", [$anneeCourante['id'] ?? 0]);
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
                                <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?>
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
                    <button type="button" class="btn btn-primary w-100" onclick="loadEC()" id="btnLoadEC">
                        <i class="bi bi-search"></i> Charger
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="notesFormCard" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-pencil-square"></i> Saisie des Notes</h5>
            <div>
                <button type="button" class="btn btn-outline-info btn-sm" onclick="calculerSemestre()">
                    <i class="bi bi-calculator"></i> Calculer Semestre
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="noteForm">
                <input type="hidden" id="noteEtudiantId">
                <input type="hidden" id="noteEcId">
                <input type="hidden" id="noteFiliereId">
                <input type="hidden" id="noteAnneeId" value="<?= $anneeCourante['id'] ?? '' ?>">
                
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
                        <button type="button" class="btn btn-primary w-100" onclick="saveNote()">
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

<script>
const ecsCache = {};

document.getElementById('etudiantSelect').addEventListener('change', function() {
    document.getElementById('ecSelect').disabled = this.value === '';
    const option = this.options[this.selectedIndex];
    if (option.dataset.semestre) {
        document.getElementById('semestreSelect').value = option.dataset.semestre;
    }
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
                select.innerHTML += `<option value="${ec.id}" data-ue="${ec.ue_nom}" data-coef="${ec.coefficient}">${ec.code} - ${ec.nom}</option>`;
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
        document.getElementById('noteEc').value = selected.text;
        
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
    const etudiantId = document.getElementById('etudiantSelect').value;
    const semestre = document.getElementById('semestreSelect').value;
    const filiereId = document.getElementById('noteFiliereId').value;
    const anneeId = document.getElementById('noteAnneeId').value;
    
    fetch('?page=notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'calculerSemestre',
            etudiant_id: etudiantId,
            semestre: semestre,
            filiere_id: filiereId,
            annee_academique_id: anneeId
        })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            const tbody = document.getElementById('moyennesBody');
            tbody.innerHTML = '';
            
            result.resultats.forEach(r => {
                tbody.innerHTML += `
                    <tr>
                        <td>${r.ue.code}</td>
                        <td>${r.ue.nom}</td>
                        <td>${r.credits}</td>
                        <td><strong>${r.moyenne ?? '-'}/20</strong></td>
                        <td>
                            ${r.moyenne ? `<span class="badge bg-${r.valide ? 'success' : 'danger'}">${r.valide ? 'Validé ✓' : 'Ajourné ✗'}</span>` : '-'}
                        </td>
                    </tr>
                `;
            });
            
            document.getElementById('moyenneSemestre').textContent = result.moyenne_semestre ?? '-';
            document.getElementById('creditsObtenus').textContent = result.credits_obtenus ?? 0;
            document.getElementById('mention').textContent = result.mention;
            document.getElementById('decision').textContent = result.decision;
            
            document.getElementById('moyennesCard').style.display = 'block';
        }
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:250px';
    toast.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
