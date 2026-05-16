<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireRole('root', 'administrateur');

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$action = $_GET['action'] ?? 'liste';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    if ($_POST['action'] === 'set_frais') {
        db()->insert('frais_scolarite', [
            'etudiant_id' => (int)$_POST['etudiant_id'],
            'annee_academique_id' => (int)($anneeCourante['id'] ?? 0),
            'montant_total' => (float)$_POST['montant'],
            'echeance1' => (float)($_POST['echeance1'] ?? 0),
            'echeance1_date' => Security::validateDate($_POST['echeance1_date'] ?? ''),
            'statut' => 'impaye'
        ]);
        $_SESSION['success'] = 'Frais enregistrés';
        header('Location: ?page=finances');
        exit;
    }
    if ($_POST['action'] === 'payer') {
        $fraisId = (int)$_POST['frais_id'];
        $montant = (float)$_POST['montant'];
        db()->insert('paiements', [
            'frais_id' => $fraisId,
            'montant' => $montant,
            'date_paiement' => Security::validateDate($_POST['date_paiement'] ?? date('Y-m-d')),
            'mode_paiement' => Security::validateEnum($_POST['mode_paiement'] ?? 'especes', ['especes', 'cheque', 'virement', 'mobile_money', 'carte'], 'especes'),
            'reference' => trim($_POST['reference'] ?? ''),
            'saisi_par' => (int)($_SESSION['user_id'] ?? 0),
        ]);
        $totalPaye = db()->fetch("SELECT COALESCE(SUM(montant),0) as total FROM paiements WHERE frais_id = ?", [$fraisId])['total'];
        $frais = db()->fetch("SELECT montant_total FROM frais_scolarite WHERE id = ?", [$fraisId]);
        $newStatut = $totalPaye >= ($frais['montant_total'] ?? 0) ? 'paye' : 'partiel';
        db()->update('frais_scolarite', ['montant_paye' => $totalPaye, 'statut' => $newStatut], 'id = :id', ['id' => $fraisId]);
        $_SESSION['success'] = 'Paiement enregistré';
        header('Location: ?page=finances');
        exit;
    }
}

Security::showSuccess();

$etudiants = db()->fetchAll("SELECT id, numero, nom, prenom FROM etudiants WHERE annee_academique_id = ? ORDER BY nom", [$anneeCourante['id'] ?? 0]);
$frais = db()->fetchAll(
    "SELECT f.*, e.numero, e.nom, e.prenom, f2.nom as filiere
     FROM frais_scolarite f
     JOIN etudiants e ON f.etudiant_id = e.id
     JOIN filieres f2 ON e.filiere_id = f2.id
     WHERE f.annee_academique_id = ?
     ORDER BY e.nom", [$anneeCourante['id'] ?? 0]
);
?>
<div class="finances-page">
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link <?= $action === 'liste' ? 'active' : '' ?>" href="?page=finances&action=liste"><i class="bi bi-list"></i> Frais de scolarité</a></li>
        <li class="nav-item"><a class="nav-link <?= $action === 'nouveau' ? 'active' : '' ?>" href="?page=finances&action=nouveau"><i class="bi bi-plus-circle"></i> Définir frais</a></li>
        <li class="nav-item"><a class="nav-link <?= $action === 'bourses' ? 'active' : '' ?>" href="?page=finances&action=bourses"><i class="bi bi-gift"></i> Bourses</a></li>
    </ul>

    <?php if ($action === 'nouveau'): ?>
    <div class="card">
        <div class="card-header"><h5>Définir les frais de scolarité</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="set_frais">
                <div class="col-md-4">
                    <label class="form-label">Étudiant *</label>
                    <select class="form-select" name="etudiant_id" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($etudiants as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= Security::h($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Montant total *</label>
                    <input type="number" class="form-control" name="montant" min="0" step="1000" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">1ère échéance</label>
                    <input type="number" class="form-control" name="echeance1" min="0" step="1000">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date échéance</label>
                    <input type="date" class="form-control" name="echeance1_date">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <?php elseif ($action === 'bourses'): ?>
    <div class="card">
        <div class="card-header"><h5>Gestion des bourses</h5></div>
        <div class="card-body">
            <p class="text-muted">Module de gestion des bourses à implémenter. Les données sont structurées dans la table <code>bourses</code>.</p>
            <ul>
                <li>Attribution de bourses par étudiant</li>
                <li>Suivi des montants et organismes</li>
                <li>Renouvellement automatique</li>
            </ul>
        </div>
    </div>

    <?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Étudiant</th><th>Filière</th><th>Montant</th><th>Payé</th><th>Reste</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($frais as $f): ?>
                    <tr>
                        <td><?= Security::h($f['prenom'] . ' ' . $f['nom']) ?><br><small class="text-muted"><?= Security::h($f['numero']) ?></small></td>
                        <td><?= Security::h($f['filiere'] ?? '') ?></td>
                        <td><strong><?= number_format((float)$f['montant_total'], 0, ',', ' ') ?> FCFA</strong></td>
                        <td><?= number_format((float)$f['montant_paye'], 0, ',', ' ') ?> FCFA</td>
                        <td><strong><?= number_format(max(0, (float)$f['montant_total'] - (float)$f['montant_paye']), 0, ',', ' ') ?> FCFA</strong></td>
                        <td>
                            <span class="badge bg-<?= $f['statut'] === 'paye' ? 'success' : ($f['statut'] === 'partiel' ? 'warning' : 'danger') ?>">
                                <?= ucfirst($f['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success btn-payer-frais" data-id="<?= $f['id'] ?>" data-total="<?= (float)$f['montant_total'] ?>" data-paye="<?= (float)$f['montant_paye'] ?>"><i class="bi bi-cash"></i> Payer</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($frais)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun frais enregistré</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="payerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Enregistrer un paiement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="payer">
                    <input type="hidden" name="frais_id" id="payerFraisId">
                    <div class="mb-3">
                        <label class="form-label">Montant *</label>
                        <input type="number" class="form-control" name="montant" id="payerMontant" min="1" step="100" required>
                        <small class="text-muted">Reste dû: <span id="payerReste"></span> FCFA</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date_paiement" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode de paiement</label>
                        <select class="form-select" name="mode_paiement">
                            <option value="especes">Espèces</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="carte">Carte bancaire</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Référence</label>
                        <input type="text" class="form-control" name="reference" placeholder="N° chèque, référence virement...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Confirmer le paiement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
function payer(id, total, paye) {
    document.getElementById('payerFraisId').value = id;
    var reste = total - paye;
    document.getElementById('payerReste').textContent = reste.toLocaleString('fr-FR');
    document.getElementById('payerMontant').max = total;
    new bootstrap.Modal(document.getElementById('payerModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-payer-frais').forEach(function(btn) {
        btn.addEventListener('click', function() { payer(btn.dataset.id, btn.dataset.total, btn.dataset.paye); });
    });
});
</script>
