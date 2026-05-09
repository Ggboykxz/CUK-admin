<?php
$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$etudiants = db()->fetchAll("SELECT id, numero, nom, prenom FROM etudiants WHERE annee_academique_id = ? ORDER BY nom", [$anneeCourante['id'] ?? 0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $data = [
            'etudiant_id' => intval($_POST['etudiant_id']),
            'type' => $_POST['type'],
            'description' => trim($_POST['description']),
            'gravite' => $_POST['gravite'],
            'date_incident' => $_POST['date_incident'],
            'lieu' => trim($_POST['lieu'] ?? ''),
            'temoin' => trim($_POST['temoin'] ?? ''),
            'utilisateur_id' => $_SESSION['user_id'],
            'mesures' => trim($_POST['mesures'] ?? ''),
            'sanction' => trim($_POST['sanction'] ?? ''),
            'statut' => 'en_cours'
        ];

        db()->insert('incidents', $data);

        $etudiant = db()->fetch("SELECT nom, prenom FROM etudiants WHERE id = ?", [$data['etudiant_id']]);
        db()->insert('journal_activite', [
            'user_id' => $_SESSION['user_id'],
            'action' => 'ajouter_incident',
            'details' => "Incident pour {$etudiant['prenom']} {$etudiant['nom']} - {$data['type']}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        $_SESSION['success'] = 'Incident enregistré';
        header('Location: ?page=disciplinarite');
        exit;
    }

    if ($action === 'traiter') {
        db()->update('incidents', [
            'mesures' => trim($_POST['mesures']),
            'sanction' => trim($_POST['sanction'] ?? ''),
            'date_mesures' => date('Y-m-d'),
            'statut' => 'traite'
        ], 'id = :id', ['id' => intval($_POST['id'])]);

        $_SESSION['success'] = 'Incident traité';
        header('Location: ?page=disciplinarite');
        exit;
    }

    if ($action === 'cloturer') {
        db()->update('incidents', ['statut' => 'cloture'], 'id = :id', ['id' => intval($_POST['id'])]);
        $_SESSION['success'] = 'Incident clôturé';
        header('Location: ?page=disciplinarite');
        exit;
    }
}

if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> ' . $_SESSION['success'] . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    unset($_SESSION['success']);
}

$incidents = db()->fetchAll("
    SELECT i.*, e.numero, e.nom, e.prenom, u.nom as signaleur
    FROM incidents i
    JOIN etudiants e ON i.etudiant_id = e.id
    JOIN users u ON i.utilisateur_id = u.id
    ORDER BY i.date_incident DESC
");

$statsIncidents = [
    'total' => count($incidents),
    'enCours' => count(array_filter($incidents, fn($i) => $i['statut'] === 'en_cours')),
    'graves' => count(array_filter($incidents, fn($i) => $i['gravite'] === 'grave'))
];
?>

<div class="disciplinarite-page">
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-shield-exclamation"></i></div>
            <div class="stat-info">
                <h3><?= $statsIncidents['total'] ?></h3>
                <p>Total Incidents</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="bi bi-clock"></i></div>
            <div class="stat-info">
                <h3><?= $statsIncidents['enCours'] ?></h3>
                <p>En Cours</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-info">
                <h3><?= $statsIncidents['graves'] ?></h3>
                <p>Incidents Graves</p>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="bi bi-plus-circle"></i> Nouveau Signalement</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="ajouter">
                <div class="col-md-4">
                    <label class="form-label">Étudiant *</label>
                    <select class="form-select" name="etudiant_id" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($etudiants as $e) : ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date *</label>
                    <input type="date" class="form-control" name="date_incident" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type *</label>
                    <select class="form-select" name="type" required>
                        <option value="retard">Retard</option>
                        <option value="absence">Absence injustifiée</option>
                        <option value="fraude">Fraude</option>
                        <option value="triche">Triche</option>
                        <option value="violence">Violence</option>
                        <option value="vandalisme">Vandalisme</option>
                        <option value="non_paiement">Non-paiement</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Gravité *</label>
                    <select class="form-select" name="gravite" required>
                        <option value="mineur">Mineur</option>
                        <option value="majeur">Majeur</option>
                        <option value="grave">Grave</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lieu</label>
                    <input type="text" class="form-control" name="lieu" placeholder="Salle, amphithéâtre...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Témoin(s)</label>
                    <input type="text" class="form-control" name="temoin" placeholder="Noms des témoins">
                </div>
                <div class="col-12">
                    <label class="form-label">Description des faits *</label>
                    <textarea class="form-control" name="description" rows="3" required placeholder="Décrivez précisément les faits..."></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-exclamation-triangle"></i> Signaler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-list"></i> Historique des Incidents</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Étudiant</th>
                        <th>Type</th>
                        <th>Gravité</th>
                        <th>Description</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidents as $i) : ?>
                    <tr class="<?= $i['gravite'] === 'grave' ? 'table-danger' : ($i['gravite'] === 'majeur' ? 'table-warning' : '') ?>">
                        <td><?= date('d/m/Y', strtotime($i['date_incident'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($i['prenom'] . ' ' . $i['nom']) ?></strong>
                            <small class="d-block text-muted"><?= htmlspecialchars($i['numero']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $i['type'] === 'fraude' || $i['type'] === 'triche' ? 'danger' : 'secondary' ?>">
                                <?= str_replace('_', ' ', ucfirst($i['type'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $i['gravite'] === 'grave' ? 'danger' : ($i['gravite'] === 'majeur' ? 'warning text-dark' : 'info') ?>">
                                <?= ucfirst($i['gravite']) ?>
                            </span>
                        </td>
                        <td>
                            <small><?= htmlspecialchars(substr($i['description'], 0, 80)) ?><?= strlen($i['description']) > 80 ? '...' : '' ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $i['statut'] === 'cloture' ? 'success' : ($i['statut'] === 'traite' ? 'info' : 'warning text-dark') ?>">
                                <?= str_replace('_', ' ', ucfirst($i['statut'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewIncident(<?= $i['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($i['statut'] !== 'traite') : ?>
                                <button class="btn btn-outline-success" onclick="traiterIncident(<?= $i['id'] ?>)">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($i['statut'] === 'traite') : ?>
                                <button class="btn btn-outline-secondary" onclick="cloturerIncident(<?= $i['id'] ?>)">
                                    <i class="bi bi-check-all"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="traiterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Traiter l'incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="traiter">
                    <input type="hidden" name="id" id="traiterId">
                    <div class="mb-3">
                        <label class="form-label">Mesures prises *</label>
                        <textarea class="form-control" name="mesures" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sanction (si applicable)</label>
                        <input type="text" class="form-control" name="sanction">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="cloturerForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="cloturer">
    <input type="hidden" name="id" id="cloturerId">
</form>

<script>
function viewIncident(id) {
    const incidents = <?= json_encode($incidents) ?>;
    const incident = incidents.find(i => i.id == id);
    
    document.getElementById('viewContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><th>Étudiant:</th><td>${incident.prenom} ${incident.nom}</td></tr>
                    <tr><th>Numéro:</th><td>${incident.numero}</td></tr>
                    <tr><th>Date:</th><td>${incident.date_incident}</td></tr>
                    <tr><th>Type:</th><td>${incident.type}</td></tr>
                    <tr><th>Gravité:</th><td>${incident.gravite}</td></tr>
                    <tr><th>Lieu:</th><td>${incident.lieu || '-'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><th>Statut:</th><td>${incident.statut}</td></tr>
                    <tr><th>Signalé par:</th><td>${incident.signaleur}</td></tr>
                    <tr><th>Témoins:</th><td>${incident.temoin || '-'}</td></tr>
                    <tr><th>Mesures:</th><td>${incident.mesures || '-'}</td></tr>
                    <tr><th>Sanction:</th><td>${incident.sanction || '-'}</td></tr>
                </table>
            </div>
            <div class="col-12">
                <h6>Description:</h6>
                <p>${incident.description}</p>
            </div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function traiterIncident(id) {
    document.getElementById('traiterId').value = id;
    new bootstrap.Modal(document.getElementById('traiterModal')).show();
}

function cloturerIncident(id) {
    if (confirm('Clôturer cet incident ?')) {
        document.getElementById('cloturerId').value = id;
        document.getElementById('cloturerForm').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#incidents') {
        new bootstrap.Tab(document.querySelector('[data-bs-target="#incidents"]')).show();
    }
});
</script>
