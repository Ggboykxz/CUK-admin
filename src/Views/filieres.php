<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Security.php';

Security::initSession();
Security::requireAuth();

$instituts = db()->fetchAll("SELECT * FROM instituts WHERE actif = 1 ORDER BY sigle");
$filieres = db()->fetchAll("SELECT f.*, i.sigle as institut, i.nom as institut_nom FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1 ORDER BY i.sigle, f.nom");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Session expirée';
        header('Location: ?page=filieres');
        exit;
    }

    if ($action === 'create_institut') {
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'nom' => trim($_POST['nom'] ?? ''),
            'sigle' => strtoupper(trim($_POST['sigle'] ?? '')),
            'description' => trim($_POST['description'] ?? ''),
            'actif' => 1
        ];
        db()->insert('instituts', $data);
        $_SESSION['success'] = 'Institut créé avec succès';
        header('Location: ?page=filieres');
        exit;
    }

    if ($action === 'create_filiere') {
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'nom' => trim($_POST['nom'] ?? ''),
            'nom_complet' => trim($_POST['nom_complet'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'institut_id' => Security::validateInt($_POST['institut_id']),
            'duree_ans' => Security::validateInt($_POST['duree'] ?? 2, 2),
            'credits_total' => Security::validateInt($_POST['credits'] ?? 120, 120)
        ];
        db()->insert('filieres', $data);

        $filiereId = db()->fetch("SELECT id FROM filieres WHERE code = ?", [$data['code']])['id'];

        for ($s = 1; $s <= 4; $s++) {
            db()->insert('semestres', [
                'code' => $data['code'] . '-S' . $s,
                'nom' => 'Semestre ' . $s,
                'numero' => $s,
                'filiere_id' => $filiereId,
                'credits' => 30
            ]);
        }

        $_SESSION['success'] = 'Filière DUT créée avec succès';
        header('Location: ?page=filieres');
        exit;
    }

    if ($action === 'create_ue') {
        $filiereCode = db()->fetch("SELECT code FROM filieres WHERE id = ?", [Security::validateInt($_POST['filiere_id'])])['code'] ?? '';
        $semestre = Security::validateInt($_POST['semestre']);
        $semestreData = db()->fetch("SELECT id FROM semestres WHERE code LIKE ? AND numero = ?", [$filiereCode . '%', $semestre]);

        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'nom' => trim($_POST['nom'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'filiere_id' => Security::validateInt($_POST['filiere_id']),
            'semestre_id' => $semestreData['id'] ?? 0,
            'credits' => floatval($_POST['credits'] ?? 0),
            'obligatoire' => isset($_POST['obligatoire']) ? 1 : 0
        ];
        db()->insert('ues', $data);
        $_SESSION['success'] = 'UE créée avec succès';
        header('Location: ?page=filieres');
        exit;
    }

    if ($action === 'create_ec') {
        $data = [
            'code' => strtoupper(trim($_POST['code'] ?? '')),
            'nom' => trim($_POST['nom'] ?? ''),
            'ue_id' => Security::validateInt($_POST['ue_id']),
            'coefficient' => floatval($_POST['coefficient'] ?? 1),
            'coefficient_cc' => floatval($_POST['coef_cc'] ?? 0.20),
            'coefficient_tp' => floatval($_POST['coef_tp'] ?? 0.20),
            'coefficient_examen' => floatval($_POST['coef_examen'] ?? 0.60),
            'type' => Security::validateEnum($_POST['type'] ?? 'mixed', ['mixed', 'theorique', 'pratique', 'tp'], 'mixed')
        ];
        db()->insert('ecs', $data);
        $_SESSION['success'] = 'EC créé avec succès';
        header('Location: ?page=filieres');
        exit;
    }
}

Security::showSuccess();
Security::showError();

$ues = db()->fetchAll("SELECT ue.*, f.nom as filiere, f.code as filiere_code, s.nom as semestre FROM ues ue JOIN filieres f ON ue.filiere_id = f.id JOIN semestres s ON ue.semestre_id = s.id WHERE ue.active = 1 ORDER BY f.code, s.numero");

$ecs = db()->fetchAll("SELECT ec.*, ue.nom as ue_nom, ue.code as ue_code FROM ecs ec JOIN ues ue ON ec.ue_id = ue.id WHERE ec.active = 1 ORDER BY ue.code");
?>

<div class="filieres-page">
    <ul class="nav nav-tabs mb-4" id="filieresTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#instituts" type="button">
            <i class="bi bi-building"></i> Instituts</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#filieres" type="button">
            <i class="bi bi-grid-3x3"></i> Filières DUT</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ues" type="button">
            <i class="bi bi-collection"></i> UE</button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#ecs" type="button">
            <i class="bi bi-book"></i> EC</button>
        </li>
    </ul>

    <div class="tab-content" id="filieresTabContent">
        <div class="tab-pane fade show active" id="instituts">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5><i class="bi bi-plus-circle"></i> Nouvel Institut</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="create_institut">
                        <div class="col-md-2">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" maxlength="10" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sigle</label>
                            <input type="text" class="form-control" name="sigle" maxlength="10" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom complet</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer l'Institut</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <?php foreach ($instituts as $inst) : ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-<?= $inst['sigle'] === 'ISTPK' ? 'primary' : 'info' ?> text-white">
                            <h4 class="mb-0"><i class="bi bi-building"></i> <?= Security::h($inst['sigle']) ?></h4>
                        </div>
                        <div class="card-body">
                            <h5><?= Security::h($inst['nom']) ?></h5>
                            <p class="text-muted"><?= Security::h($inst['description'] ?? '') ?></p>
                            <hr>
                            <h6>Filières DUT:</h6>
                            <ul>
                                <?php $filieresInst = array_filter($filieres, fn($f) => $f['institut_id'] == $inst['id']); ?>
                                <?php foreach ($filieresInst as $f) : ?>
                                <li><?= Security::h($f['nom']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="filieres">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-plus-circle"></i> Nouvelle Filière DUT</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="create_filiere">
                        <div class="col-md-2">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" name="code" maxlength="20" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom complet</label>
                            <input type="text" class="form-control" name="nom_complet" placeholder="DUT en ...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Institut *</label>
                            <select class="form-select" name="institut_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($instituts as $inst) : ?>
                                    <option value="<?= $inst['id'] ?>"><?= Security::h($inst['sigle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Durée (ans)</label>
                            <input type="number" class="form-control" name="duree" value="2" min="1" max="3">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Crédits total</label>
                            <input type="number" class="form-control" name="credits" value="120" min="60" max="180">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer la Filière DUT</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list"></i> Liste des Filières DUT</h5></div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr><th>Institut</th><th>Code</th><th>Filière DUT</th><th>Durée</th><th>Crédits</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filieres as $f) : ?>
                            <tr>
                                <td><span class="badge bg-<?= $f['institut'] === 'ISTPK' ? 'primary' : 'info' ?>"><?= Security::h($f['institut']) ?></span></td>
                                <td><strong><?= Security::h($f['code']) ?></strong></td>
                                <td><?= Security::h($f['nom']) ?></td>
                                <td><?= $f['duree_ans'] ?> ans</td>
                                <td><?= $f['credits_total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="ues">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-plus-circle"></i> Nouvelle UE</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="create_ue">
                        <div class="col-md-2">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" name="code" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filière DUT *</label>
                            <select class="form-select" name="filiere_id" id="ueFiliere" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($filieres as $f) : ?>
                                    <option value="<?= $f['id'] ?>"><?= Security::h($f['code'] . ' - ' . $f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Semestre *</label>
                            <select class="form-select" name="semestre" required>
                                <option value="1">S1</option>
                                <option value="2">S2</option>
                                <option value="3">S3</option>
                                <option value="4">S4</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Crédits *</label>
                            <input type="number" class="form-control" name="credits" min="1" max="30" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="obligatoire" name="obligatoire" checked>
                                <label class="form-check-label" for="obligatoire">UE obligatoire</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list"></i> Liste des UE</h5></div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr><th>Code</th><th>UE</th><th>Filière</th><th>Semestre</th><th>Crédits</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ues as $ue) : ?>
                            <tr>
                                <td><strong><?= Security::h($ue['code']) ?></strong></td>
                                <td><?= Security::h($ue['nom']) ?></td>
                                <td><?= Security::h($ue['filiere_code']) ?></td>
                                <td><span class="badge bg-secondary">S<?= substr($ue['semestre'], -1) ?></span></td>
                                <td><?= $ue['credits'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="ecs">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-plus-circle"></i> Nouvel EC (Matière)</h5></div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="create_ec">
                        <div class="col-md-2">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" name="code" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UE parente *</label>
                            <select class="form-select" name="ue_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($ues as $ue) : ?>
                                    <option value="<?= $ue['id'] ?>"><?= Security::h($ue['code'] . ' - ' . $ue['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coefficient *</label>
                            <input type="number" class="form-control" name="coefficient" value="1" min="1" max="10" step="0.5" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="mixed">Mixte</option>
                                <option value="theorique">Théorique</option>
                                <option value="pratique">Pratique</option>
                                <option value="tp">TP</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coef. CC</label>
                            <input type="number" class="form-control" name="coef_cc" value="0.20" step="0.05">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coef. TP</label>
                            <input type="number" class="form-control" name="coef_tp" value="0.20" step="0.05">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coef. Examen</label>
                            <input type="number" class="form-control" name="coef_examen" value="0.60" step="0.05">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list"></i> Liste des EC</h5></div>
                <div class="card-body">
                    <table class="table table-hover w-100">
                        <thead>
                            <tr><th>Code</th><th>EC</th><th>UE</th><th>Coef.</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ecs as $ec) : ?>
                            <tr>
                                <td><strong><?= Security::h($ec['code']) ?></strong></td>
                                <td><?= Security::h($ec['nom']) ?></td>
                                <td><?= Security::h($ec['ue_code']) ?></td>
                                <td><?= $ec['coefficient'] ?></td>
                                <td><span class="badge bg-secondary"><?= Security::h($ec['type']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
