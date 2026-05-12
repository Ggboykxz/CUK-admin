<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$semaine = (int)($_GET['semaine'] ?? (int)date('W'));
$jour = (int)($_GET['jour'] ?? 0);
$ecs = db()->fetchAll("SELECT ec.*, ue.nom as ue_nom FROM ecs ec JOIN ues ue ON ec.ue_id = ue.id WHERE ec.active = 1 ORDER BY ue.nom, ec.code");
$enseignants = db()->fetchAll("SELECT id, nom, prenom, grade FROM utilisateurs WHERE actif = 1 ORDER BY nom");
$salles = db()->fetchAll("SELECT * FROM salles ORDER BY code");

$cours = db()->fetchAll(
    "SELECT c.*, ec.nom as ec_nom, ec.code as ec_code, u.nom as ens_nom, u.prenom as ens_prenom, s.nom as salle_nom
     FROM cours c
     JOIN ecs ec ON c.ec_id = ec.id
     LEFT JOIN utilisateurs u ON c.enseignant_id = u.id
     LEFT JOIN salles s ON c.salle = s.code
     WHERE c.annee_academique_id = ?
     ORDER BY c.jour_semaine, c.heure_debut", [$anneeCourante['id'] ?? 0]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        db()->insert('cours', [
            'ec_id' => (int)$_POST['ec_id'],
            'enseignant_id' => !empty($_POST['enseignant_id']) ? (int)$_POST['enseignant_id'] : null,
            'salle' => trim($_POST['salle'] ?? ''),
            'jour_semaine' => (int)$_POST['jour_semaine'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin'],
            'type_seance' => Security::validateEnum($_POST['type_seance'] ?? 'CM', ['CM', 'TD', 'TP'], 'CM'),
            'groupe' => trim($_POST['groupe'] ?? ''),
            'semestre' => Security::validateEnum($_POST['semestre'] ?? 'S1', ['S1', 'S2', 'S3', 'S4'], 'S1'),
            'annee_academique_id' => (int)($anneeCourante['id'] ?? 0),
        ]);
        $_SESSION['success'] = 'Cours ajouté';
        header('Location: ?page=cours');
        exit;
    }
    if ($action === 'delete') {
        db()->delete('cours', 'id = :id', ['id' => (int)$_POST['id']]);
        $_SESSION['success'] = 'Cours supprimé';
        header('Location: ?page=cours');
        exit;
    }
}

Security::showSuccess();

$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$coursParJour = [];
foreach ($cours as $c) {
    $coursParJour[$c['jour_semaine']][] = $c;
}
?>
<div class="cours-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-calendar-week"></i> Emploi du temps</h4>
        <?php if (in_array($_SESSION['user_role'] ?? '', ['root', 'administrateur'], true)): ?>
        <button class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('coursModal')).show()"><i class="bi bi-plus-circle"></i> Ajouter un cours</button>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th style="width:80px;">Horaire</th>
                    <?php for ($d = 1; $d <= 5; $d++): ?>
                    <th><?= $jours[$d - 1] ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($h = 8; $h <= 18; $h += 2): ?>
                <tr>
                    <td class="text-center align-middle"><strong><?= sprintf('%02d:00', $h) ?></strong></td>
                    <?php for ($d = 1; $d <= 5; $d++): ?>
                    <td style="height:80px;vertical-align:top;">
                        <?php $found = false; if (isset($coursParJour[$d])): foreach ($coursParJour[$d] as $c):
                            $hd = (int)substr($c['heure_debut'], 0, 2);
                            $hf = (int)substr($c['heure_fin'], 0, 2);
                            if ($hd >= $h && $hd < $h + 2):
                            $found = true;
                        ?>
                        <div class="p-1 mb-1 rounded bg-<?= $c['type_seance'] === 'TP' ? 'info' : ($c['type_seance'] === 'TD' ? 'warning' : 'primary') ?> text-white" style="font-size:11px;cursor:pointer;" onclick="viewCours(<?= $c['id'] ?>, '<?= Security::h($c['ec_code'] ?? '') ?>', '<?= Security::h($c['ec_nom'] ?? '') ?>', '<?= Security::h(($c['ens_prenom'] ?? '') . ' ' . ($c['ens_nom'] ?? '')) ?>', '<?= Security::h($c['salle_nom'] ?? $c['salle'] ?? '') ?>', '<?= $c['heure_debut'] ?>', '<?= $c['heure_fin'] ?>', '<?= $c['type_seance'] ?>')">
                            <strong><?= Security::h($c['ec_code'] ?? '') ?></strong><br>
                            <small><?= Security::h($c['salle_nom'] ?? $c['salle'] ?? '') ?></small>
                        </div>
                        <?php endif; endforeach; endif; if (!$found): ?>
                        <div style="height:100%;">&nbsp;</div>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="coursModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle"></i> Ajouter un cours</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <?= Security::csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">EC *</label>
                            <select class="form-select" name="ec_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($ecs as $ec): ?>
                                <option value="<?= $ec['id'] ?>"><?= Security::h($ec['code'] . ' - ' . $ec['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enseignant</label>
                            <select class="form-select" name="enseignant_id">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($enseignants as $ens): ?>
                                <option value="<?= $ens['id'] ?>"><?= Security::h($ens['prenom'] . ' ' . $ens['nom']) ?> (<?= Security::h($ens['grade'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jour *</label>
                            <select class="form-select" name="jour_semaine" required>
                                <?php for ($d = 1; $d <= 5; $d++): ?>
                                <option value="<?= $d ?>"><?= $jours[$d - 1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Début *</label>
                            <input type="time" class="form-control" name="heure_debut" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fin *</label>
                            <input type="time" class="form-control" name="heure_fin" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Salle</label>
                            <select class="form-select" name="salle">
                                <option value="">Sélectionner...</option>
                                <?php foreach ($salles as $s): ?>
                                <option value="<?= Security::h($s['code']) ?>"><?= Security::h($s['code'] . ' - ' . $s['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="type_seance">
                                <option value="CM">CM</option>
                                <option value="TD">TD</option>
                                <option value="TP">TP</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Semestre</label>
                            <select class="form-select" name="semestre">
                                <option value="S1">S1</option><option value="S2">S2</option><option value="S3">S3</option><option value="S4">S4</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Groupe</label>
                            <input type="text" class="form-control" name="groupe" placeholder="Groupe A, B, etc.">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewCoursModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewCoursTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="viewCoursBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button></div>
        </div>
    </div>
</div>

<script>
function viewCours(id, code, nom, enseignant, salle, debut, fin, type) {
    document.getElementById('viewCoursTitle').textContent = code + ' - ' + nom;
    document.getElementById('viewCoursBody').innerHTML = '<table class="table table-sm"><tr><th>Enseignant:</th><td>' + enseignant + '</td></tr><tr><th>Salle:</th><td>' + salle + '</td></tr><tr><th>Horaire:</th><td>' + debut + ' - ' + fin + '</td></tr><tr><th>Type:</th><td>' + type + '</td></tr></table>';
    new bootstrap.Modal(document.getElementById('viewCoursModal')).show();
}
</script>
