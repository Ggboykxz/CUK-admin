<?php
$anneeCourante = db()->fetch("SELECT id, annee FROM annees_academiques WHERE courante = 1");
$instituts = db()->fetchAll("SELECT * FROM instituts WHERE actif = 1 ORDER BY sigle");
$filieres = db()->fetchAll("SELECT f.*, i.nom as institut_nom, i.sigle as institut_sigle FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1 ORDER BY i.sigle, f.nom");

if (isset($_GET['action']) && $_GET['action'] === 'get') {
    $id = intval($_GET['id']);
    $etudiant = db()->fetch("
        SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut, i.nom as institut_nom 
        FROM etudiants e 
        JOIN filieres f ON e.filiere_id = f.id 
        JOIN instituts i ON f.institut_id = i.id 
        WHERE e.id = ?", [$id]);
    echo json_encode($etudiant);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $numero = 'ETU-' . date('Y') . '-' . str_pad(db()->fetch("SELECT COUNT(*)+1 as count FROM etudiants")['count'], 3, '0', STR_PAD_LEFT);
        $matricule = 'MAT-' . date('Y') . '-' . substr(md5(uniqid()), 0, 6);
        
        $data = [
            'numero' => $numero,
            'matricule' => $matricule,
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'sexe' => $_POST['sexe'],
            'date_naissance' => $_POST['date_naissance'],
            'lieu_naissance' => trim($_POST['lieu_naissance'] ?? ''),
            'nationalite' => trim($_POST['nationalite'] ?? 'Gabonaise'),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'filiere_id' => intval($_POST['filiere_id']),
            'semestre' => $_POST['semestre'] ?? 'S1',
            'annee_academique_id' => intval($_POST['annee_academique_id']),
            'date_inscription' => date('Y-m-d'),
            'boursier' => isset($_POST['boursier']) ? 1 : 0,
            'statut' => 'actif'
        ];
        
        $id = db()->insert('etudiants', $data);
        
        db()->insert('journal_activite', [
            'user_id' => $_SESSION['user_id'],
            'action' => 'create_etudiant',
            'table_concernee' => 'etudiants',
            'id_concerne' => $id,
            'details' => "Nouvel étudiant: {$data['prenom']} {$data['nom']}",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        $_SESSION['success'] = 'Étudiant enregistré avec succès';
        header('Location: ?page=etudiants');
        exit;
    }
    
    if ($action === 'update') {
        $id = intval($_POST['id']);
        $data = [
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'sexe' => $_POST['sexe'],
            'date_naissance' => $_POST['date_naissance'],
            'lieu_naissance' => trim($_POST['lieu_naissance'] ?? ''),
            'nationalite' => trim($_POST['nationalite'] ?? 'Gabonaise'),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'filiere_id' => intval($_POST['filiere_id']),
            'semestre' => $_POST['semestre'] ?? 'S1',
            'boursier' => isset($_POST['boursier']) ? 1 : 0,
            'statut' => $_POST['statut'] ?? 'actif',
            'observation' => trim($_POST['observation'] ?? '')
        ];
        
        db()->update('etudiants', $data, 'id = :id', ['id' => $id]);
        $_SESSION['success'] = 'Étudiant modifié avec succès';
        header('Location: ?page=etudiants');
        exit;
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        db()->delete('etudiants', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = 'Étudiant supprimé';
        header('Location: ?page=etudiants');
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

$etudiants = db()->fetchAll("
    SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut, i.nom as institut_nom
    FROM etudiants e
    JOIN filieres f ON e.filiere_id = f.id
    JOIN instituts i ON f.institut_id = i.id
    ORDER BY i.sigle, f.nom, e.nom
");
?>

<div class="etudiants-page">
    <div class="page-actions d-flex justify-content-between align-items-center mb-4">
        <div class="filters">
            <select class="form-select" id="filterInstitut">
                <option value="">Tous les Instituts</option>
                <?php foreach ($instituts as $inst): ?>
                    <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['sigle'] . ' - ' . $inst['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" id="filterFiliere">
                <option value="">Toutes les Filières DUT</option>
                <?php foreach ($filieres as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-select" id="filterSemestre">
                <option value="">Tous les Semestres</option>
                <option value="S1">S1</option>
                <option value="S2">S2</option>
                <option value="S3">S3</option>
                <option value="S4">S4</option>
            </select>
            <select class="form-select" id="filterStatut">
                <option value="">Tous les Statuts</option>
                <option value="actif">Actif</option>
                <option value="suspendu">Suspendu</option>
                <option value="diplome">Diplômé</option>
                <option value="abandon">Abandon</option>
                <option value="redoublant">Redoublant</option>
            </select>
        </div>
        <?php if ($_SESSION['user_role'] !== 'professeur'): ?>
        <button class="btn btn-primary" onclick="openModal('etudiant', 'create')">
            <i class="bi bi-plus-circle"></i> Nouvel Étudiant
        </button>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="etudiantsTable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Étudiant</th>
                        <th>Institut</th>
                        <th>Filière DUT</th>
                        <th>Semestre</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $e): ?>
                    <tr data-filiere="<?= $e['filiere_id'] ?>" data-semestre="<?= $e['semestre'] ?>" data-statut="<?= $e['statut'] ?>">
                        <td><strong><?= htmlspecialchars($e['numero']) ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="student-avatar">
                                    <?= strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></strong>
                                    <small class="d-block text-muted"><?= htmlspecialchars($e['email'] ?? '') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= $e['institut'] === 'ISTPK' ? 'primary' : 'info' ?>">
                                <?= htmlspecialchars($e['institut']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($e['filiere']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($e['semestre']) ?></span></td>
                        <td>
                            <span class="badge bg-<?= $e['statut'] === 'actif' ? 'success' : ($e['statut'] === 'suspendu' ? 'danger' : 'secondary') ?>">
                                <?= ucfirst($e['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewEtudiant(<?= $e['id'] ?>)" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($_SESSION['user_role'] !== 'professeur'): ?>
                                <button class="btn btn-outline-secondary" onclick="openModal('etudiant', 'edit', <?= $e['id'] ?>)" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $e['id'] ?>)" title="Supprimer">
                                    <i class="bi bi-trash"></i>
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

<div class="modal fade" id="etudiantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> <span id="modalTitle">Nouvel Étudiant</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom *</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom *</label>
                        <input type="text" class="form-control" name="prenom" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sexe *</label>
                        <select class="form-select" name="sexe" required>
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date de naissance *</label>
                        <input type="date" class="form-control" name="date_naissance" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nationalité</label>
                        <input type="text" class="form-control" name="nationalite" value="Gabonaise">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lieu de naissance</label>
                        <input type="text" class="form-control" name="lieu_naissance">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Institut *</label>
                        <select class="form-select" id="institutSelect" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($instituts as $inst): ?>
                                <option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['sigle'] . ' - ' . $inst['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Filière DUT *</label>
                        <select class="form-select" name="filiere_id" id="filiereSelect" required>
                            <option value="">Sélectionner d'abord un Institut...</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Semestre *</label>
                        <select class="form-select" name="semestre" required>
                            <option value="S1">S1</option>
                            <option value="S2">S2</option>
                            <option value="S3">S3</option>
                            <option value="S4">S4</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Année *</label>
                        <select class="form-select" name="annee_academique_id" required>
                            <option value="<?= $anneeCourante['id'] ?? '' ?>"><?= htmlspecialchars($anneeCourante['annee'] ?? 'Sélectionner') ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="boursier">
                            <span class="form-check-label">Boursier</span>
                        </label>
                    </div>
                    <div class="col-12" id="statutField" style="display:none;">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="statut">
                            <option value="actif">Actif</option>
                            <option value="suspendu">Suspendu</option>
                            <option value="redoublant">Redoublant</option>
                            <option value="diplome">Diplômé</option>
                            <option value="abandon">Abandon</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observations</label>
                        <textarea class="form-control" name="observation" rows="2"></textarea>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person"></i> Détails de l'Étudiant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent"></div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<style>
.student-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}
.page-actions .filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.page-actions .form-select {
    width: auto;
    min-width: 160px;
}
</style>

<script>
const filieresParInstitut = <?= json_encode($filieres) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const table = $('#etudiantsTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
        pageLength: 25,
        order: [[2, 'asc'], [3, 'asc'], [1, 'asc']]
    });

    $('#filterInstitut, #filterFiliere, #filterSemestre, #filterStatut').on('change', function() {
        const filiere = $('#filterFiliere').val();
        const semestre = $('#filterSemestre').val();
        const statut = $('#filterStatut').val();
        
        table.column(3).search(filiere);
        table.column(4).search(semestre);
        table.column(5).search(statut);
        table.draw();
    });

    $('#institutSelect').on('change', function() {
        const instId = this.value;
        const filiereSelect = document.getElementById('filiereSelect');
        filiereSelect.innerHTML = '<option value="">Sélectionner...</option>';
        
        if (instId) {
            const filieres = filieresParInstitut.filter(f => f.institut_id == instId);
            filieres.forEach(f => {
                filiereSelect.innerHTML += `<option value="${f.id}">${f.nom}</option>`;
            });
        }
    });
});

function openModal(type, action, id = null) {
    const modal = new bootstrap.Modal(document.getElementById('etudiantModal'));
    document.getElementById('formAction').value = action;
    document.getElementById('formId').value = id || '';
    document.getElementById('modalTitle').textContent = action === 'create' ? 'Nouvel Étudiant' : 'Modifier l\'Étudiant';
    document.getElementById('statutField').style.display = action === 'edit' ? 'block' : 'none';
    
    if (action === 'edit' && id) {
        fetch(`?page=etudiants&action=get&id=${id}`)
            .then(r => r.json())
            .then(data => {
                document.querySelector('[name="nom"]').value = data.nom || '';
                document.querySelector('[name="prenom"]').value = data.prenom || '';
                document.querySelector('[name="sexe"]').value = data.sexe || 'M';
                document.querySelector('[name="date_naissance"]').value = data.date_naissance || '';
                document.querySelector('[name="lieu_naissance"]').value = data.lieu_naissance || '';
                document.querySelector('[name="nationalite"]').value = data.nationalite || 'Gabonaise';
                document.querySelector('[name="telephone"]').value = data.telephone || '';
                document.querySelector('[name="email"]').value = data.email || '';
                document.querySelector('[name="adresse"]').value = data.adresse || '';
                document.querySelector('[name="statut"]').value = data.statut || 'actif';
                document.querySelector('[name="semestre"]').value = data.semestre || 'S1';
                document.querySelector('[name="observation"]').value = data.observation || '';
                if (data.boursier) document.querySelector('[name="boursier"]').checked = true;
                
                const instSelect = document.getElementById('institutSelect');
                const filiereSelect = document.getElementById('filiereSelect');
                filiereSelect.innerHTML = '<option value="">Sélectionner...</option>';
                
                const filieres = filieresParInstitut.filter(f => f.institut_id == data.filiere_id);
                filieres.forEach(f => {
                    filiereSelect.innerHTML += `<option value="${f.id}">${f.nom}</option>`;
                });
                document.querySelector('[name="filiere_id"]').value = data.filiere_id || '';
            });
    }
    
    modal.show();
}

function viewEtudiant(id) {
    fetch(`?page=etudiants&action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('viewContent').innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="student-avatar" style="width:100px;height:100px;font-size:36px;margin:0 auto 20px;">
                            ${data.prenom[0]}${data.nom[0]}
                        </div>
                        <h4>${data.prenom} ${data.nom}</h4>
                        <p class="text-muted">${data.numero}</p>
                        <span class="badge bg-${data.statut === 'actif' ? 'success' : 'secondary'}">${data.statut}</span>
                    </div>
                    <div class="col-md-8">
                        <table class="table table-sm">
                            <tr><th>Institut:</th><td><span class="badge bg-${data.institut === 'ISTPK' ? 'primary' : 'info'}">${data.institut}</span> ${data.institut_nom}</td></tr>
                            <tr><th>Filière DUT:</th><td>${data.filiere}</td></tr>
                            <tr><th>Semestre:</th><td><span class="badge bg-secondary">${data.semestre}</span></td></tr>
                            <tr><th>Sexe:</th><td>${data.sexe === 'M' ? 'Masculin' : 'Féminin'}</td></tr>
                            <tr><th>Date de naissance:</th><td>${data.date_naissance}</td></tr>
                            <tr><th>Nationalité:</th><td>${data.nationalite}</td></tr>
                            <tr><th>Téléphone:</th><td>${data.telephone || '-'}</td></tr>
                            <tr><th>Email:</th><td>${data.email || '-'}</td></tr>
                            <tr><th>Boursier:</th><td>${data.boursier ? 'Oui' : 'Non'}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        });
}

function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>