<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$anneeCourante = db()->fetch("SELECT * FROM annees_academiques WHERE courante = 1");
$instituts = db()->fetchAll("SELECT * FROM instituts WHERE actif = 1 ORDER BY sigle");
$filieres = db()->fetchAll("SELECT f.*, i.sigle as institut_sigle FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1 ORDER BY i.sigle, f.nom");

$stats = [
    'etudiants' => db()->fetch("SELECT COUNT(*) as count FROM etudiants WHERE annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['count'],
    'etudiants_actifs' => db()->fetch("SELECT COUNT(*) as count FROM etudiants WHERE statut = 'actif' AND annee_academique_id = ?", [$anneeCourante['id'] ?? 0])['count'],
    'filieres' => db()->fetch("SELECT COUNT(*) as count FROM filieres WHERE active = 1")['count'],
    'utilisateurs' => db()->fetch("SELECT COUNT(*) as count FROM users WHERE actif = 1")['count'],
];

$statsParInstitut = db()->fetchAll("SELECT i.sigle, i.nom, COUNT(e.id) as count FROM instituts i LEFT JOIN filieres f ON i.id = f.institut_id LEFT JOIN etudiants e ON f.id = e.filiere_id AND e.annee_academique_id = ? GROUP BY i.id, i.sigle, i.nom", [$anneeCourante['id'] ?? 0]);

$etudiantsRecents = db()->fetchAll("SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id ORDER BY e.created_at DESC LIMIT 5");

$absencesRecentes = db()->fetchAll("SELECT a.*, e.nom, e.prenom, ec.nom as matiere FROM absences a JOIN etudiants e ON a.etudiant_id = e.id LEFT JOIN ecs ec ON a.ec_id = ec.id ORDER BY a.date_absence DESC LIMIT 5");
?>

<div class="dashboard">
    <div class="welcome-banner mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="bi bi-building"></i> Centre Universitaire de Koulamoutou</h2>
                <p class="mb-0">Système de Gestion Universitaire - Cycle DUT</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-success fs-6 px-3 py-2">
                    <i class="bi bi-calendar3"></i> <?= Security::h($anneeCourante['annee'] ?? '') ?>
                </span>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <h3><?= $stats['etudiants'] ?></h3>
                <p>Total Étudiants</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="bi bi-person-check"></i></div>
            <div class="stat-info">
                <h3><?= $stats['etudiants_actifs'] ?></h3>
                <p>Étudiants Actifs</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-info"><i class="bi bi-grid"></i></div>
            <div class="stat-info">
                <h3><?= $stats['filieres'] ?></h3>
                <p>Filières DUT</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="bi bi-person-badge"></i></div>
            <div class="stat-info">
                <h3><?= $stats['utilisateurs'] ?></h3>
                <p>Utilisateurs</p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-building"></i> Effectifs par Institut</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <?php foreach ($statsParInstitut as $inst) : ?>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 border rounded">
                                <h4 class="mb-1"><?= $inst['count'] ?></h4>
                                <small class="text-muted"><?= Security::h($inst['sigle']) ?></small>
                                <div class="progress mt-2" style="height:8px;">
                                    <div class="progress-bar bg-<?= $inst['sigle'] === 'ISTPK' ? 'primary' : 'info' ?>" style="width:<?= min(100, $inst['count'] * 10) ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-calendar-x"></i> Absences Récentes</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($absencesRecentes)) : ?>
                        <div class="empty-state"><i class="bi bi-check-circle"></i><p>Aucune absence récente</p></div>
                    <?php else : ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($absencesRecentes as $absence) : ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= Security::h($absence['prenom'] . ' ' . $absence['nom']) ?></strong>
                                        <span class="badge <?= $absence['justifiee'] ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $absence['justifiee'] ? 'Justifié' : 'Non justifié' ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($absence['date_absence'])) ?> - <?= $absence['nombre_heures'] ?>h</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-person-plus"></i> Dernières Inscriptions</h5>
                    <a href="?page=etudiants" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($etudiantsRecents)) : ?>
                        <div class="empty-state"><i class="bi bi-people"></i><p>Aucune inscription récente</p></div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Institut</th>
                                        <th>Filière DUT</th>
                                        <th>Semestre</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiantsRecents as $etudiant) : ?>
                                        <tr>
                                            <td><strong><?= Security::h($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></strong></td>
                                            <td><span class="badge bg-<?= $etudiant['institut'] === 'ISTPK' ? 'primary' : 'info' ?>"><?= Security::h($etudiant['institut']) ?></span></td>
                                            <td><?= Security::h($etudiant['filiere']) ?></td>
                                            <td><span class="badge bg-secondary"><?= Security::h($etudiant['semestre']) ?></span></td>
                                            <td><span class="badge bg-<?= $etudiant['statut'] === 'actif' ? 'success' : 'secondary' ?>"><?= ucfirst($etudiant['statut']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


