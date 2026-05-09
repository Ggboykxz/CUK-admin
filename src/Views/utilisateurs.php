<?php
if ($_SESSION['user_role'] !== 'root' && $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'username' => trim($_POST['username']),
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => $_POST['role'],
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'email' => trim($_POST['email'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'actif' => 1
        ];
        
        db()->insert('users', $data);
        $_SESSION['success'] = 'Utilisateur créé';
        header('Location: ?page=utilisateurs');
        exit;
    }
    
    if ($action === 'update') {
        $id = intval($_POST['id']);
        $data = [
            'nom' => trim($_POST['nom']),
            'prenom' => trim($_POST['prenom']),
            'email' => trim($_POST['email'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'role' => $_POST['role'],
            'actif' => isset($_POST['actif']) ? 1 : 0
        ];
        
        if (!empty($_POST['password'])) {
            $data['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        db()->update('users', $data, 'id = :id', ['id' => $id]);
        $_SESSION['success'] = 'Utilisateur modifié';
        header('Location: ?page=utilisateurs');
        exit;
    }
    
    if ($action === 'delete') {
        db()->delete('users', 'id = :id', ['id' => intval($_POST['id'])]);
        $_SESSION['success'] = 'Utilisateur supprimé';
        header('Location: ?page=utilisateurs');
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

$utilisateurs = db()->fetchAll("SELECT * FROM users ORDER BY role, nom");
$journal = db()->fetchAll("SELECT j.*, u.username FROM journal_activite j LEFT JOIN users u ON j.user_id = u.id ORDER BY j.created_at DESC LIMIT 50");
?>

<div class="utilisateurs-page">
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#users" type="button">
            <i class="bi bi-people"></i> Utilisateurs</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#journal" type="button">
            <i class="bi bi-clock-history"></i> Journal</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="users">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-person-plus"></i> Nouvel Utilisateur</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-2">
                            <label class="form-label">Nom d'utilisateur *</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rôle *</label>
                            <select class="form-select" name="role" required>
                                <?php if ($_SESSION['user_role'] === 'root'): ?>
                                <option value="root">Root</option>
                                <?php endif; ?>
                                <option value="administrateur">Administrateur</option>
                                <option value="secretaire">Secrétaire</option>
                                <option value="professeur">Professeur</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list"></i> Liste des Utilisateurs</h5></div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Dernière connexion</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></strong>
                                    <small class="d-block text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $u['role'] === 'root' ? 'dark' : ($u['role'] === 'administrateur' ? 'primary' : ($u['role'] === 'secretaire' ? 'info' : 'secondary')) ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td><?= $u['derniere_connexion'] ? date('d/m/Y H:i', strtotime($u['derniere_connexion'])) : 'Jamais' ?></td>
                                <td>
                                    <span class="badge bg-<?= $u['actif'] ? 'success' : 'danger' ?>">
                                        <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editUser(<?= $u['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>)">
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

        <div class="tab-pane fade" id="journal">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-clock-history"></i> Journal d'Activité</h5></div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Détails</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($journal as $j): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($j['created_at'])) ?></td>
                                <td><?= htmlspecialchars($j['username'] ?? 'System') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($j['action']) ?></span></td>
                                <td><?= htmlspecialchars($j['details'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($j['ip_address'] ?? '-') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" id="editNom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" id="editPrenom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" id="editTel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle *</label>
                            <select class="form-select" name="role" id="editRole" required>
                                <option value="administrateur">Administrateur</option>
                                <option value="secretaire">Secrétaire</option>
                                <option value="professeur">Professeur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="password" placeholder="Laisser vide pour ne pas changer">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="editActif" name="actif" checked>
                                <label class="form-check-label" for="editActif">Compte actif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
const users = <?= json_encode($utilisateurs) ?>;

function editUser(id) {
    const u = users.find(x => x.id == id);
    document.getElementById('editId').value = u.id;
    document.getElementById('editNom').value = u.nom;
    document.getElementById('editPrenom').value = u.prenom;
    document.getElementById('editEmail').value = u.email || '';
    document.getElementById('editTel').value = u.telephone || '';
    document.getElementById('editRole').value = u.role;
    document.getElementById('editActif').checked = u.actif == 1;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteUser(id) {
    if (confirm('Supprimer cet utilisateur ?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>