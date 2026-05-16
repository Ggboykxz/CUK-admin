<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireRole('root', 'administrateur');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Session expirée';
        header('Location: ?page=parametres');
        exit;
    }

    if ($action === 'update_param') {
        $cle = trim($_POST['cle'] ?? '');
        $valeur = trim($_POST['valeur'] ?? '');

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
            'annee' => trim($_POST['annee'] ?? ''),
            'debut' => Security::validateDate($_POST['debut'] ?? ''),
            'fin' => Security::validateDate($_POST['fin'] ?? ''),
            'courante' => isset($_POST['courante']) ? 1 : 0,
            'active' => 1
        ]);
        $_SESSION['success'] = 'Année académique créée';
        header('Location: ?page=parametres');
        exit;
    }

    if ($action === 'disable_2fa') {
        db()->update('users', ['twofa_secret' => null, 'twofa_actif' => 0], 'id = :id', ['id' => $_SESSION['user_id']]);
        Security::logActivity('2fa_desactive', '2FA désactivé');
        $_SESSION['success'] = '2FA désactivé';
        header('Location: ?page=parametres');
        exit;
    }
}

Security::showSuccess();
Security::showError();

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
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="update_param">
                        <input type="hidden" name="cle" value="nom_etablissement">
                        <div class="col-md-6">
                            <label class="form-label">Nom de l'établissement</label>
                            <input type="text" class="form-control" name="valeur" value="<?= Security::h($paramMap['nom_etablissement'] ?? 'Centre Universitaire de Koulamoutou') ?>">
                        </div>
                        <div class="col-12"><hr><h6>Coordonnées</h6></div>
                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="valeur" value="<?= Security::h($paramMap['adresse'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="valeur" value="<?= Security::h($paramMap['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="valeur" value="<?= Security::h($paramMap['email'] ?? '') ?>">
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
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="update_param">
                        <div class="col-md-4">
                            <label class="form-label">Seuil de réussite (sur 20)</label>
                            <input type="number" class="form-control" name="valeur" step="0.5" min="0" max="20" value="<?= Security::h($paramMap['seuil_reussite'] ?? '10') ?>">
                            <input type="hidden" name="cle" value="seuil_reussite">
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
                        <?= Security::csrfField() ?>
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
                            <?php foreach ($annees as $a) : ?>
                            <tr>
                                <td><strong><?= Security::h($a['annee']) ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($a['debut'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($a['fin'])) ?></td>
                                <td>
                                    <?php if ($a['courante']) : ?>
                                        <span class="badge bg-success">Courante</span>
                                    <?php elseif ($a['active']) : ?>
                                        <span class="badge bg-secondary">Active</span>
                                    <?php else : ?>
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
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-database"></i> Sauvegarde et Restauration</h5></div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-4 border rounded text-center">
                                <i class="bi bi-download" style="font-size:40px;color:var(--primary);"></i>
                                <h6 class="mt-2">Créer une sauvegarde</h6>
                                <p class="text-muted small">Copie complète de la base de données</p>
                                <button class="btn btn-primary" onclick="creerBackup()"><i class="bi bi-download"></i> Sauvegarder</button>
                                <div id="backupResult" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border rounded">
                                <h6><i class="bi bi-server"></i> Sauvegardes disponibles</h6>
                                <div id="backupList" class="mt-2">
                                    <p class="text-muted small mb-0">Chargement...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-shield-lock"></i> Authentification à deux facteurs (2FA)</h5></div>
                <div class="card-body">
                    <div id="twoFactorSection">
                        <?php
$user2fa = db()->fetch('SELECT "twofa_actif", "twofa_secret" FROM users WHERE id = ?', [$_SESSION['user_id'] ?? 0]);
$is2faActive = !empty($user2fa['twofa_actif']);
                        ?>
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6><i class="bi bi-<?= $is2faActive ? 'check-circle-fill text-success' : 'exclamation-circle' ?>"></i>
                                    <?= $is2faActive ? '2FA activé' : '2FA désactivé' ?></h6>
                                <p class="text-muted small mb-0">
                                    <?= $is2faActive
                                        ? 'Votre compte est protégé par une authentification à deux facteurs.'
                                        : 'Activez la double authentification pour renforcer la sécurité de votre compte.' ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($is2faActive): ?>
                                <form method="POST" style="display:inline;">
                                    <?= Security::csrfField() ?>
                                    <button class="btn btn-outline-danger btn-sm" name="action" value="disable_2fa"><i class="bi bi-x-circle"></i> Désactiver</button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-primary btn-sm" onclick="setup2FA()"><i class="bi bi-shield"></i> Activer</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$is2faActive): ?>
                        <div id="twoFactorSetup" class="mt-4 p-4 bg-light rounded" style="display:none;">
                            <div class="row">
                                <div class="col-md-5 text-center">
                                    <div id="qrcodeContainer" class="mb-3">
                                        <div style="width:180px;height:180px;background:#f0f0f0;margin:0 auto;display:flex;align-items:center;justify-content:center;border-radius:8px;border:2px dashed #ccc;">
                                            <i class="bi bi-qr-code" style="font-size:64px;color:#999;"></i>
                                        </div>
                                    </div>
                                    <p class="small text-muted">Scannez ce code avec Google Authenticator ou Authy</p>
                                    <p class="small"><code id="secretKey"></code></p>
                                </div>
                                <div class="col-md-7">
                                    <h6>Vérification</h6>
                                    <p class="text-muted small">Entrez le code à 6 chiffres généré par votre application</p>
                                    <div class="input-group mb-3" style="max-width:200px;">
                                        <input type="text" class="form-control text-center" id="code2FA" maxlength="6" placeholder="000000">
                                        <button class="btn btn-success" onclick="verifier2FA()">Vérifier</button>
                                    </div>
                                    <div id="twoFAResult"></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?= nonce_attr() ?>>
// === BACKUP ===
function chargerBackups() {
    fetch('../api/backup.php?action=list')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var list = document.getElementById('backupList');
            if (data.length === 0) {
                list.innerHTML = '<p class="text-muted small mb-0">Aucune sauvegarde</p>';
                return;
            }
            var html = '<div class="list-group list-group-flush">';
            data.forEach(function(b) {
                html += '<div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">' +
                    '<div><small><strong>' + b.file + '</strong></small><br><small class="text-muted">' + b.date + ' - ' + b.size_formatted + '</small></div>' +
                    '<div class="btn-group btn-group-sm">' +
                    '<button class="btn btn-outline-success" onclick="restaurerBackup(\'' + b.file + '\')"><i class="bi bi-upload"></i></button>' +
                    '<button class="btn btn-outline-danger" onclick="supprimerBackup(\'' + b.file + '\')"><i class="bi bi-trash"></i></button>' +
                    '</div></div>';
            });
            html += '</div>';
            list.innerHTML = html;
        });
}

function creerBackup() {
    var btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass"></i> Création...';
    document.getElementById('backupResult').innerHTML = '';

    fetch('../api/backup.php?action=create')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('backupResult').innerHTML = '<div class="alert alert-success py-1 small"><i class="bi bi-check-circle"></i> Backup créé: ' + data.file + '</div>';
                chargerBackups();
            } else {
                document.getElementById('backupResult').innerHTML = '<div class="alert alert-danger py-1 small">' + (data.error || 'Erreur') + '</div>';
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-download"></i> Sauvegarder';
        });
}

function restaurerBackup(file) {
    if (!confirm('Restaurer la base de données depuis "' + file + '" ? Cette action est irréversible.')) return;
    fetch('../api/backup.php?action=restore&file=' + encodeURIComponent(file))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Restauration réussie !');
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Échec'));
            }
        });
}

function supprimerBackup(file) {
    if (!confirm('Supprimer "' + file + '" ?')) return;
    fetch('../api/backup.php?action=delete&file=' + encodeURIComponent(file))
        .then(function(r) { return r.json(); })
        .then(function() { chargerBackups(); });
}

chargerBackups();

// === 2FA ===
var currentSecret = '';

function setup2FA() {
    document.getElementById('twoFactorSetup').style.display = 'block';
    currentSecret = generateSecret();
    document.getElementById('secretKey').textContent = currentSecret;
}

function generateSecret() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    var secret = '';
    for (var i = 0; i < 16; i++) {
        secret += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return secret;
}

function verifier2FA() {
    var code = document.getElementById('code2FA').value.trim();
    if (code.length !== 6) {
        document.getElementById('twoFAResult').innerHTML = '<div class="text-danger small">Code à 6 chiffres requis</div>';
        return;
    }

    fetch('api/2fa_verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            _csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '',
            secret: currentSecret,
            code: code
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('twoFAResult').innerHTML = '<div class="text-success small"><i class="bi bi-check-circle"></i> 2FA activé avec succès !</div>';
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            document.getElementById('twoFAResult').innerHTML = '<div class="text-danger small">' + (data.error || 'Code invalide') + '</div>';
        }
    });
}
</script>
