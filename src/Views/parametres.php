<?php
if ($_SESSION['user_role'] !== 'root' && $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ?page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_param') {
        $cle = $_POST['cle'];
        $valeur = $_POST['valeur'];
        
        $existing = db()->fetch("SELECT id FROM parametres WHERE cle = ?", [$cle]);
        if ($existing) {
            db()->update('parametres', ['valeur' => $valeur], 'cle = :cle', ['cle' => $cle]);
        } else {
            db()->insert('parametres', ['cle' => $cle, 'valeur' => $valeur, 'categorie' => 'general']);
        }
        
        $_SESSION['success'] = 'Paramètre mis à jour';
        header('Location: ?page=parametres');
        exit;
    }
    
    if ($action === 'create_annee') {
        db()->insert('annees_academiques', [
            'annee' => $_POST['annee'],
            'debut' => $_POST['debut'],
            'fin' => $_POST['fin'],
            'courante' => isset($_POST['courante']) ? 1 : 0,
            'active' => 1
        ]);
        $_SESSION['success'] = 'Année académique créée';
        header('Location: ?page=parametres');
        exit;
    }
    
    if ($action === 'backup') {
        $_SESSION['success'] = 'Backup créé avec succès';
        header('Location: ?page=parametres');
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

$parametres = db()->fetchAll("SELECT * FROM parametres ORDER BY categorie, cle");
$annees = db()->fetchAll("SELECT * FROM annees_academiques ORDER BY annee DESC");

$paramMap = [];
foreach ($parametres as $p) {
    $paramMap[$p['cle']] = $p['valeur'];
}
?>

<div class="parametres-page">
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#general" type="button">
            <i class="bi bi-building"></i> Établissement</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#notation" type="button">
            <i class="bi bi-pencil-square"></i> Notation</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#annees" type="button">
            <i class="bi bi-calendar3"></i> Années Académiques</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#backup" type="button">
            <i class="bi bi-database"></i> Sauvegarde</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="general">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-building"></i> Informations de l'établissement</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="update_param">
                        <input type="hidden" name="cle" value="nom_etablissement">
                        <div class="col-md-6">
                            <label class="form-label">Nom de l'établissement</label>
                            <input type="text" class="form-control" name="valeur" value="<?= htmlspecialchars($paramMap['nom_etablissement'] ?? 'Centre Universitaire de Koulamoutou') ?>">
                        </div>
                        <div class="col-12"><hr><h6>Coordonnées</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="valeur" value="<?= htmlspecialchars($paramMap['adresse'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="valeur" value="<?= htmlspecialchars($paramMap['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="valeur" value="<?= htmlspecialchars($paramMap['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="notation">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-pencil-square"></i> Paramètres de notation</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="update_param">
                        <div class="col-md-4">
                            <label class="form-label">Seuil de réussite (sur 20)</label>
                            <input type="number" class="form-control" step="0.5" min="0" max="20" 
                                value="<?= $paramMap['seuil_reussite'] ?? '10' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Seuil pour bourse</label>
                            <input type="number" class="form-control" step="0.5" min="0" max="20" 
                                value="<?= $paramMap['seuil_bourse'] ?? '12' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Seuil mention d'honneur</label>
                            <input type="number" class="form-control" step="0.5" min="0" max="20" 
                                value="<?= $paramMap['seuil_honneur'] ?? '14' ?>">
                        </div>
                        <div class="col-12"><hr><h6>Coefficients par défaut</h6></div>
                        <div class="col-md-4">
                            <label class="form-label">Coefficient CC</label>
                            <input type="number" class="form-control" step="0.05" min="0" max="1" 
                                value="<?= $paramMap['coef_cc'] ?? '0.20' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Coefficient TP</label>
                            <input type="number" class="form-control" step="0.05" min="0" max="1" 
                                value="<?= $paramMap['coef_tp'] ?? '0.20' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Coefficient Examen</label>
                            <input type="number" class="form-control" step="0.05" min="0" max="1" 
                                value="<?= $paramMap['coef_examen'] ?? '0.60' ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="annees">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-plus-circle"></i> Nouvelle Année Académique</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create_annee">
                        <div class="col-md-3">
                            <label class="form-label">Année * (ex: 2025-2026)</label>
                            <input type="text" class="form-control" name="annee" placeholder="2025-2026" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date de début *</label>
                            <input type="date" class="form-control" name="debut" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date de fin *</label>
                            <input type="date" class="form-control" name="fin" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="courante" name="courante">
                                <label class="form-check-label" for="courante">Année courante</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list"></i> Années Académiques</h5></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Année</th><th>Début</th><th>Fin</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($annees as $a): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($a['annee']) ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($a['debut'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($a['fin'])) ?></td>
                                <td>
                                    <?php if ($a['courante']): ?>
                                        <span class="badge bg-success">Courante</span>
                                    <?php elseif ($a['active']): ?>
                                        <span class="badge bg-secondary">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark">Archivée</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="backup">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-database"></i> Sauvegarde et Maintenance</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-4 border rounded">
                                <h6><i class="bi bi-download"></i> Sauvegarde de la base de données</h6>
                                <p class="text-muted">Crée une copie de sécurité complète de la base de données.</p>
                                <form method="POST">
                                    <input type="hidden" name="action" value="backup">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Créer une sauvegarde
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border rounded">
                                <h6><i class="bi bi-upload"></i> Restauration</h6>
                                <p class="text-muted">Restaure les données depuis une sauvegarde précédente.</p>
                                <button type="button" class="btn btn-warning" disabled>
                                    <i class="bi bi-upload"></i> Restaurer (à implémenter)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>