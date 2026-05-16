<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireRole('root', 'administrateur');

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT * FROM filieres WHERE active = 1 ORDER BY nom");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_pv') {
        $id = db()->insert('pvs_jury', [
            'annee_academique_id' => (int)($anneeCourante['id'] ?? 0),
            'semestre' => Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1'),
            'filiere_id' => (int)$_POST['filiere_id'],
            'date_jury' => Security::validateDate($_POST['date_jury'] ?? date('Y-m-d')),
            'lieu' => trim($_POST['lieu'] ?? ''),
            'president' => trim($_POST['president'] ?? ''),
            'secretaire' => trim($_POST['secretaire'] ?? ''),
            'membres' => trim($_POST['membres'] ?? ''),
            'observations' => trim($_POST['observations'] ?? ''),
            'created_by' => (int)($_SESSION['user_id'] ?? 0),
        ]);
        $_SESSION['success'] = 'PV de jury créé';
        if ($_POST['generer_resultats'] ?? false) {
            genererResultats((int)($anneeCourante['id'] ?? 0), (int)$_POST['filiere_id'], Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1'));
        }
        header('Location: ?page=jury');
        exit;
    }
}

Security::showSuccess();

$pvs = db()->fetchAll(
    "SELECT p.*, f.nom as filiere_nom, u.nom as createur_nom
     FROM pvs_jury p
     LEFT JOIN filieres f ON p.filiere_id = f.id
     LEFT JOIN users u ON p.created_by = u.id
     ORDER BY p.date_jury DESC"
);

function genererResultats(int $anneeId, int $filiereId, string $semestre): void
{
    $etudiants = db()->fetchAll(
        "SELECT id, nom, prenom, numero FROM etudiants WHERE filiere_id = ? AND semestre = ? AND annee_academique_id = ? AND statut = 'actif'",
        [$filiereId, $semestre, $anneeId]
    );

    $semestreData = db()->fetch(
        "SELECT s.id, s.numero FROM semestres s JOIN filieres f ON s.filiere_id = f.id WHERE f.id = ? AND s.numero = ?",
        [$filiereId, (int)substr($semestre, 1)]
    );

    if (!$semestreData) return;

    foreach ($etudiants as $e) {
        $moyenne = db()->fetch(
            "SELECT moyenne_semestre, credits_obtenus, total_credits FROM moyennes_semestrielles WHERE etudiant_id = ? AND semestre_id = ? AND annee_academique_id = ?",
            [$e['id'], $semestreData['id'], $anneeId]
        );
        if ($moyenne) {
            $decision = ($moyenne['moyenne_semestre'] ?? 0) >= 10 ? 'admis' : 'ajourne';
            $existing = db()->fetch(
                "SELECT id FROM resultats_annuels WHERE etudiant_id = ? AND annee_academique_id = ?",
                [$e['id'], $anneeId]
            );
            $data = [
                'etudiant_id' => $e['id'],
                'annee_academique_id' => $anneeId,
                'moyenne_annuelle' => $moyenne['moyenne_semestre'],
                'credits_obtenus' => (int)($moyenne['credits_obtenus'] ?? 0),
                'total_credits' => (int)($moyenne['total_credits'] ?? 0),
                'decision' => $decision,
                'date_jury' => date('Y-m-d'),
            ];
            if ($existing) {
                db()->update('resultats_annuels', $data, 'id = :id', ['id' => $existing['id']]);
            } else {
                db()->insert('resultats_annuels', $data);
            }
        }
    }
}
?>
<div class="jury-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-file-earmark-text"></i> Délibération / Jury</h4>
        <button class="btn btn-primary" id="btnAjouterJury">
            <i class="bi bi-plus-circle"></i> Nouveau PV
        </button>
    </div>

    <div class="card">
        <div class="card-header"><h5><i class="bi bi-list"></i> Procès-verbaux de jury</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Filière</th><th>Semestre</th><th>Président</th><th>Lieu</th><th>Créé par</th></tr></thead>
                <tbody>
                    <?php foreach ($pvs as $pv): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($pv['date_jury'])) ?></td>
                        <td><?= Security::h($pv['filiere_nom'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary"><?= Security::h($pv['semestre'] ?? '') ?></span></td>
                        <td><?= Security::h($pv['president'] ?? '-') ?></td>
                        <td><?= Security::h($pv['lieu'] ?? '-') ?></td>
                        <td><?= Security::h($pv['createur_nom'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pvs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun PV de jury</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="juryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Nouveau PV de jury</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_pv">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Filière *</label>
                            <select class="form-select" name="filiere_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($filieres as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= Security::h($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Semestre *</label>
                            <select class="form-select" name="semestre" required>
                                <option value="S1">S1</option><option value="S2">S2</option><option value="S3">S3</option><option value="S4">S4</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date_jury" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lieu</label>
                            <input type="text" class="form-control" name="lieu" placeholder="Salle de réunion">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Président *</label>
                            <input type="text" class="form-control" name="president" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secrétaire *</label>
                            <input type="text" class="form-control" name="secretaire" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Membres</label>
                            <input type="text" class="form-control" name="membres" placeholder="Noms séparés par des virgules">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observations</label>
                            <textarea class="form-control" name="observations" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="genererResultats" name="generer_resultats" value="1">
                                <label class="form-check-label" for="genererResultats">Générer automatiquement les résultats (admis/ajourné) dans <code>resultats_annuels</code></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Créer le PV</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnAjouterJury').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('juryModal')).show();
    });
});
</script>
