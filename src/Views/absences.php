<?php
$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$etudiants = db()->fetchAll("SELECT id, numero, nom, prenom FROM etudiants WHERE annee_academique_id = ? ORDER BY nom", [$anneeCourante['id'] ?? 0]);

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'ajouter') {
        $data = [
            'etudiant_id' => intval($_POST['etudiant_id']),
            'ec_id' => $_POST['ec_id'] ? intval($_POST['ec_id']) : null,
            'annee_academique_id' => intval($_POST['annee_academique_id']),
            'date_absence' => $_POST['date_absence'],
            'nombre_heures' => intval($_POST['nombre_heures'] ?? 2),
            'justifiee' => isset($_POST['justifiee']) ? 1 : 0,
            'motif' => trim($_POST['motif'] ?? ''),
            'saisi_par' => $_SESSION['user_id']
        ];
        
        db()->insert('absences', $data);
        
        $etudiant = db()->fetch("SELECT nom, prenom FROM etudiants WHERE id = ?", [$data['etudiant_id']]);
        db()->insert('journal_activite', [
            'user_id' => $_SESSION['user_id'],
            'action' => 'ajouter_absence',
            'table_concernee' => 'absences',
            'details' => "Absence pour {$etudiant['prenom']} {$etudiant['nom']}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        $_SESSION['success'] = 'Absence enregistrée avec succès';
        header('Location: ?page=absences');
        exit;
    }
    
    if ($_POST['action'] === 'justifier') {
        db()->update('absences', [
            'justifiee' => 1,
            'motif' => trim($_POST['motif'] ?? '')
        ], 'id = :id', ['id' => intval($_POST['id'])]);
        
        $_SESSION['success'] = 'Absence justifiée';
        header('Location: ?page=absences');
        exit;
    }
    
    if ($_POST['action'] === 'supprimer') {
        db()->delete('absences', 'id = :id', ['id' => intval($_POST['id'])]);
        $_SESSION['success'] = 'Absence supprimée';
        header('Location: ?page=absences');
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

$absences = db()->fetchAll("
    SELECT a.*, e.numero, e.nom, e.prenom, f.nom as filiere, n.nom as niveau,
           ec.nom as matiere
    FROM absences a
    JOIN etudiants e ON a.etudiant_id = e.id
    JOIN filieres f ON e.filiere_id = f.id
    JOIN niveaux n ON e.niveau_id = n.id
    LEFT JOIN ecs ec ON a.ec_id = ec.id
    WHERE a.annee_academique_id = ?
    ORDER BY a.date_absence DESC
", [$anneeCourante['id'] ?? 0]);

$statsAbsences = [
    'total' => count($absences),
    'justifiees' => count(array_filter($absences, fn($a) => $a['justifiee'])),
    'nonJustifiees' => count(array_filter($absences, fn($a) => !$a['justifiee']))
];
?>

<div class="absences-page">
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
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="annee_academique_id" value="<?= $anneeCourante['id'] ?? '' ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Étudiant *</label>
                    <select class="form-select" name="etudiant_id" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($etudiants as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom'] . ' (' . $e['numero'] . ')') ?></option>
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
                    <?php foreach ($absences as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></strong>
                            <small class="d-block text-muted"><?= htmlspecialchars($a['numero']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($a['filiere']) ?></td>
                        <td><?= $a['nombre_heures'] ?>h</td>
                        <td>
                            <?php if ($a['justifiee']): ?>
                                <span class="badge bg-success">Justifiée</span>
                                <?php if ($a['motif']): ?>
                                    <small class="d-block text-muted"><?= htmlspecialchars($a['motif']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger">Non justifiée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?php if (!$a['justifiee']): ?>
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
</script>