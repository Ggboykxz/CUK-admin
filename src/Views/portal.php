<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();

$error = '';
$vue = $_GET['vue'] ?? 'accueil';

// Student auth - uses etudiants.password_hash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $numero = trim($_POST['numero'] ?? '');
    $password = $_POST['password'] ?? '';

    $etudiant = db()->fetch("SELECT * FROM etudiants WHERE numero = :numero AND statut = 'actif'", ['numero' => $numero]);

    if ($etudiant && !empty($etudiant['password_hash']) && password_verify($password, $etudiant['password_hash'])) {
        Security::regenerateSession();
        $_SESSION['student_id'] = $etudiant['id'];
        $_SESSION['student_nom'] = $etudiant['prenom'] . ' ' . $etudiant['nom'];
        $_SESSION['student_numero'] = $etudiant['numero'];
    } else {
        $error = 'Numéro ou mot de passe incorrect';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['student_id'], $_SESSION['student_nom'], $_SESSION['student_numero']);
    header('Location: ?page=portal');
    exit;
}

$isLoggedIn = isset($_SESSION['student_id']);

if ($isLoggedIn) {
    $sid = (int)$_SESSION['student_id'];
    $etudiant = db()->fetch(
        "SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut
         FROM etudiants e
         JOIN filieres f ON e.filiere_id = f.id
         JOIN instituts i ON f.institut_id = i.id
         WHERE e.id = ?", [$sid]
    );

    $notes = db()->fetchAll(
        "SELECT n.*, ec.code as ec_code, ec.nom as ec_nom, ec.coefficient, ue.nom as ue_nom
         FROM notes n
         JOIN ecs ec ON n.ec_id = ec.id
         JOIN ues ue ON ec.ue_id = ue.id
         WHERE n.etudiant_id = ?
         ORDER BY ue.nom, ec.code", [$sid]
    );

    $absences = db()->fetchAll(
        "SELECT a.*, ec.nom as matiere
         FROM absences a
         LEFT JOIN ecs ec ON a.ec_id = ec.id
         WHERE a.etudiant_id = ?
         ORDER BY a.date_absence DESC LIMIT 50", [$sid]
    );

    $moyennes = db()->fetchAll(
        "SELECT ms.*, s.nom as semestre_nom
         FROM moyennes_semestrielles ms
         JOIN semestres s ON ms.semestre_id = s.id
         WHERE ms.etudiant_id = ?
         ORDER BY s.numero", [$sid]
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Étudiant - CUK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .portal-header { background: linear-gradient(135deg, #1e3a5f, #2d5a87); color: white; padding: 20px 0; }
        .portal-nav { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 12px 0; }
        .stat-etudiant { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-notes th { background: #f8f9fa; }
        .badge-abs { padding: 4px 10px; border-radius: 6px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-mortarboard"></i> Portail Étudiant</h2>
                    <p class="mb-0 opacity-75">Centre Universitaire de Koulamoutou</p>
                </div>
                <?php if ($isLoggedIn): ?>
                <a href="?page=portal&logout=1" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isLoggedIn && $etudiant): ?>
    <div class="portal-nav">
        <div class="container">
            <div class="d-flex align-items-center gap-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="student-avatar" style="width:44px;height:44px;font-size:18px;"><?= Security::h(strtoupper(substr($etudiant['prenom']??'',0,1).substr($etudiant['nom']??'',0,1))) ?></div>
                    <div><strong><?= Security::h($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></strong><br><small class="text-muted"><?= Security::h($etudiant['numero']) ?></small></div>
                </div>
                <span class="badge bg-primary"><?= Security::h($etudiant['institut'] ?? '') ?></span>
                <span class="badge bg-secondary"><?= Security::h($etudiant['filiere'] ?? '') ?></span>
                <span class="badge bg-info">Semestre <?= Security::h($etudiant['semestre'] ?? '') ?></span>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link <?= $vue === 'accueil' ? 'active' : '' ?>" href="?page=portal&vue=accueil"><i class="bi bi-speedometer2"></i> Accueil</a></li>
            <li class="nav-item"><a class="nav-link <?= $vue === 'notes' ? 'active' : '' ?>" href="?page=portal&vue=notes"><i class="bi bi-mortarboard"></i> Notes</a></li>
            <li class="nav-item"><a class="nav-link <?= $vue === 'absences' ? 'active' : '' ?>" href="?page=portal&vue=absences"><i class="bi bi-calendar-x"></i> Absences</a></li>
            <li class="nav-item"><a class="nav-link <?= $vue === 'releves' ? 'active' : '' ?>" href="?page=portal&vue=releves"><i class="bi bi-file-pdf"></i> Relevés</a></li>
        </ul>

        <?php if ($vue === 'accueil'): ?>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="stat-etudiant text-center">
                    <i class="bi bi-mortarboard" style="font-size:32px;color:#1e3a5f;"></i>
                    <h3 class="mt-2"><?= count($notes) ?></h3>
                    <p class="text-muted mb-0">Notes enregistrées</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-etudiant text-center">
                    <i class="bi bi-calendar-x" style="font-size:32px;color:#dc3545;"></i>
                    <h3 class="mt-2"><?= count($absences) ?></h3>
                    <p class="text-muted mb-0">Absences</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-etudiant text-center">
                    <i class="bi bi-trophy" style="font-size:32px;color:#28a745;"></i>
                    <h3 class="mt-2"><?= count($moyennes) ?></h3>
                    <p class="text-muted mb-0">Semestres validés</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-etudiant text-center">
                    <i class="bi bi-building" style="font-size:32px;color:#17a2b8;"></i>
                    <h3 class="mt-2"><?= Security::h($etudiant['institut'] ?? '-') ?></h3>
                    <p class="text-muted mb-0">Institut</p>
                </div>
            </div>
        </div>

        <?php elseif ($vue === 'notes'): ?>
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-table"></i> Mes notes</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>UE</th><th>EC</th><th>Coef.</th><th>CC</th><th>TP</th><th>Examen</th><th>Moyenne</th></tr></thead>
                        <tbody>
                            <?php $currentUe = ''; foreach ($notes as $n): ?>
                            <?php if ($currentUe !== $n['ue_nom']): $currentUe = $n['ue_nom']; ?>
                            <tr class="table-light"><td colspan="7"><strong><?= Security::h($n['ue_nom']) ?></strong></td></tr>
                            <?php endif; ?>
                            <tr>
                                <td></td>
                                <td><?= Security::h($n['ec_code']) ?> - <?= Security::h($n['ec_nom']) ?></td>
                                <td><?= $n['coefficient'] ?></td>
                                <td><?= $n['cc'] !== null ? number_format((float)$n['cc'], 2) : '-' ?></td>
                                <td><?= $n['tp'] !== null ? number_format((float)$n['tp'], 2) : '-' ?></td>
                                <td><?= $n['examen'] !== null ? number_format((float)$n['examen'], 2) : '-' ?></td>
                                <td><strong><?= $n['moyenne_ec'] !== null ? number_format((float)$n['moyenne_ec'], 2) : '-' ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($notes)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune note disponible</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($vue === 'absences'): ?>
        <div class="card">
            <div class="card-header"><h5><i class="bi bi-calendar-x"></i> Mes absences</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Matière</th><th>Heures</th><th>Statut</th><th>Motif</th></tr></thead>
                        <tbody>
                            <?php foreach ($absences as $a): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($a['date_absence'])) ?></td>
                                <td><?= Security::h($a['matiere'] ?? '-') ?></td>
                                <td><?= $a['nombre_heures'] ?>h</td>
                                <td><span class="badge-abs bg-<?= $a['justifiee'] ? 'success' : 'danger' ?> text-white"><?= $a['justifiee'] ? 'Justifiée' : 'Non justifiée' ?></span></td>
                                <td><?= Security::h($a['motif'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($absences)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-check-circle"></i> Aucune absence</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($vue === 'releves'): ?>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card text-center h-100">
                    <div class="card-body py-5">
                        <i class="bi bi-file-pdf" style="font-size:48px;color:#dc3545;"></i>
                        <h5 class="mt-3">Relevé de notes</h5>
                        <p class="text-muted">Téléchargez votre relevé de notes complet au format PDF.</p>
                        <a href="../api/export.php?type=releve&id=<?= $sid ?>" class="btn btn-danger" target="_blank"><i class="bi bi-download"></i> Télécharger</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card text-center h-100">
                    <div class="card-body py-5">
                        <i class="bi bi-file-pdf" style="font-size:48px;color:#1e3a5f;"></i>
                        <h5 class="mt-3">Bulletin</h5>
                        <p class="text-muted">Téléchargez votre bulletin de notes officiel.</p>
                        <a href="../api/export.php?type=bulletin&id=<?= $sid ?>" class="btn btn-primary" target="_blank"><i class="bi bi-download"></i> Télécharger</a>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5><i class="bi bi-trophy"></i> Résultats semestriels</h5></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Semestre</th><th>Moyenne</th><th>Crédits</th><th>Validation</th><th>Mention</th></tr></thead>
                            <tbody>
                                <?php foreach ($moyennes as $m): ?>
                                <tr>
                                    <td><strong><?= Security::h($m['semestre_nom'] ?? '') ?></strong></td>
                                    <td><?= $m['moyenne_semestre'] !== null ? number_format((float)$m['moyenne_semestre'], 2) . '/20' : '-' ?></td>
                                    <td><?= (int)$m['credits_obtenus'] ?> / <?= (int)$m['total_credits'] ?></td>
                                    <td><span class="badge bg-<?= $m['validation'] === 'valide' ? 'success' : 'warning' ?>"><?= ucfirst($m['validation'] ?? 'non_valide') ?></span></td>
                                    <td><?= Security::h($m['mention_semestre'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($moyennes)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Aucun résultat disponible</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-circle" style="font-size:64px;color:var(--primary);"></i>
                            <h4 class="mt-2">Connexion étudiant</h4>
                            <p class="text-muted">Entrez votre numéro d'étudiant et votre mot de passe</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= Security::h($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Numéro d'étudiant</label>
                                <input type="text" class="form-control" name="numero" required placeholder="ETU-2025-001">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" name="password" required>
                                <small class="text-muted">Mot de passe défini par l'administration</small>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Se connecter</button>
                        </form>

                        <hr>
                        <p class="text-center text-muted mb-0">
                            <small>Portail étudiant - Centre Universitaire de Koulamoutou</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
