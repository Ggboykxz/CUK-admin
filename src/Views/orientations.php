<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$filieres = db()->fetchAll("SELECT * FROM filieres WHERE active = 1 ORDER BY nom");
$etudiants = db()->fetchAll("SELECT id, numero, nom, prenom FROM etudiants WHERE annee_academique_id = ? AND statut = 'actif' ORDER BY nom", [$anneeCourante['id'] ?? 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Session expirée';
        header('Location: ?page=orientations');
        exit;
    }

    if ($action === 'creer') {
        $type = Security::validateEnum($_POST['type'] ?? '', ['orientation', 'transfert', 'reorientation', 'passage_licence'], 'orientation');

        $data = [
            'etudiant_id' => Security::validateInt($_POST['etudiant_id']),
            'filiere_cible_id' => Security::validateInt($_POST['filiere_cible_id']),
            'annee_academique_id' => Security::validateInt($_POST['annee_academique_id']),
            'type' => $type,
            'mention' => trim($_POST['mention'] ?? ''),
            'rang' => !empty($_POST['rang']) ? Security::validateInt($_POST['rang']) : null,
            'avis_enseignant' => trim($_POST['avis_enseignant'] ?? ''),
            'decision' => 'en_attente',
            'utilisateur_id' => $_SESSION['user_id'],
            'observation' => trim($_POST['observation'] ?? '')
        ];

        if (!empty($_POST['filiere_origine_id'])) {
            $data['filiere_origine_id'] = Security::validateInt($_POST['filiere_origine_id']);
        }

        db()->insert('orientations', $data);
        $_SESSION['success'] = 'Orientation enregistrée';
        header('Location: ?page=orientations');
        exit;
    }

    if ($action === 'decider') {
        $id = Security::validateInt($_POST['id']);
        $decision = Security::validateEnum($_POST['decision'] ?? '', ['accepte', 'refuse', 'en_attente', 'report'], 'en_attente');

        db()->update('orientations', [
            'decision' => $decision,
            'avis_conseil' => trim($_POST['avis_conseil'] ?? ''),
            'date_decision' => date('Y-m-d'),
            'observation' => trim($_POST['observation'] ?? '')
        ], 'id = :id', ['id' => $id]);

        if ($decision === 'accepte') {
            $orientation = db()->fetch("SELECT * FROM orientations WHERE id = ?", [$id]);
            if ($orientation) {
                db()->update('etudiants', [
                    'filiere_id' => $orientation['filiere_cible_id'],
                    'statut' => 'actif'
                ], 'id = :id', ['id' => $orientation['etudiant_id']]);
            }
        }

        $_SESSION['success'] = 'Décision enregistrée';
        header('Location: ?page=orientations');
        exit;
    }
}

Security::showSuccess();
Security::showError();

$orientations = db()->fetchAll("SELECT o.*, e.numero, e.nom, e.prenom, fo.nom as filiere_origine, fc.nom as filiere_cible FROM orientations o JOIN etudiants e ON o.etudiant_id = e.id LEFT JOIN filieres fo ON o.filiere_origine_id = fo.id LEFT JOIN filieres fc ON o.filiere_cible_id = fc.id ORDER BY o.date_orientation DESC");
?>

<div class="orientations-page">
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-plus-circle"></i> Nouvelle Orientation / Transfert</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="creer">
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
                <div class="col-md-2">
                    <label class="form-label">Type *</label>
                    <select class="form-select" name="type" required>
                        <option value="orientation">Orientation</option>
                        <option value="transfert">Transfert</option>
                        <option value="reorientation">Réorientation</option>
                        <option value="passage_licence">Passage Licence</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filière cible *</label>
                    <select class="form-select" name="filiere_cible_id" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($filieres as $f) : ?>
                            <option value="<?= $f['id'] ?>"><?= Security::h($f['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mention</label>
                    <input type="text" class="form-control" name="mention" placeholder="Ex: Bien, Très Bien">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rang</label>
                    <input type="number" class="form-control" name="rang" min="1">
                </div>
                <div class="col-12">
                    <label class="form-label">Avis de l'enseignant</label>
                    <textarea class="form-control" name="avis_enseignant" rows="2"></textarea>
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
            <h5><i class="bi bi-list"></i> Historique des Orientations</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Type</th>
                        <th>Origine</th>
                        <th>Cible</th>
                        <th>Mention/Rang</th>
                        <th>Décision</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orientations as $o) : ?>
                    <tr>
                        <td>
                            <strong><?= Security::h($o['prenom'] . ' ' . $o['nom']) ?></strong>
                            <small class="d-block text-muted"><?= Security::h($o['numero']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= ucfirst($o['type']) ?></span>
                        </td>
                        <td><?= Security::h($o['filiere_origine'] ?? '-') ?></td>
                        <td><?= Security::h($o['filiere_cible'] ?? '-') ?></td>
                        <td>
                            <?= Security::h($o['mention'] ?: '-') ?>
                            <?php if ($o['rang']) : ?>
                                <small class="d-block text-muted">Rang: <?= $o['rang'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $o['decision'] === 'accepte' ? 'success' : ($o['decision'] === 'refuse' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($o['decision']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($o['decision'] === 'en_attente') : ?>
                            <button class="btn btn-sm btn-outline-success" onclick="deciderOrientation(<?= $o['id'] ?>)">
                                <i class="bi bi-check"></i> Décider
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deciderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Décision d'orientation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="decider">
                    <input type="hidden" name="id" id="deciderId">
                    <div class="mb-3">
                        <label class="form-label">Décision *</label>
                        <select class="form-select" name="decision" required>
                            <option value="accepte">Accepté</option>
                            <option value="refuse">Refusé</option>
                            <option value="report">Reporté</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Avis du conseil</label>
                        <textarea class="form-control" name="avis_conseil" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observation</label>
                        <input type="text" class="form-control" name="observation">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deciderOrientation(id) {
    document.getElementById('deciderId').value = id;
    new bootstrap.Modal(document.getElementById('deciderModal')).show();
}
</script>
