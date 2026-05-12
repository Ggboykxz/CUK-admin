<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;

Security::initSession();
Security::requireAuth();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Session expirée']);
    exit;
}

if ($action === 'get_classe') {
    $filiereId = (int)($_POST['filiere_id'] ?? 0);
    $semestre = Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1');

    $etudiants = db()->fetchAll(
        "SELECT id, numero, nom, prenom FROM etudiants
         WHERE filiere_id = ? AND semestre = ? AND statut = 'actif'
         ORDER BY nom", [$filiereId, $semestre]
    );

    echo json_encode(['success' => true, 'etudiants' => $etudiants]);
    exit;
}

if ($action === 'save_appel') {
    $filiereId = (int)($_POST['filiere_id'] ?? 0);
    $semestre = Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1');
    $date = Security::validateDate($_POST['date_appel'] ?? '');
    $ecId = !empty($_POST['ec_id']) ? (int)$_POST['ec_id'] : null;
    $presences = json_decode($_POST['presences'] ?? '[]', true);
    $anneeCourante = db()->fetch("SELECT id FROM annees_academiques WHERE courante = 1");
    $anneeId = $anneeCourante['id'] ?? 0;

    if (!$date || empty($presences)) {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit;
    }

    $count = 0;
    foreach ($presences as $p) {
        $etudiantId = (int)($p['id'] ?? 0);
        $present = !empty($p['present']);

        if (!$etudiantId) continue;

        if (!$present) {
            $existing = db()->fetch(
                "SELECT id FROM absences WHERE etudiant_id = ? AND date_absence = ? AND ec_id ?",
                [$etudiantId, $date, $ecId]
            );
            if (!$existing) {
                db()->insert('absences', [
                    'etudiant_id' => $etudiantId,
                    'ec_id' => $ecId,
                    'annee_academique_id' => $anneeId,
                    'date_absence' => $date,
                    'nombre_heures' => 2,
                    'justifiee' => 0,
                    'motif' => 'Absent lors de l\'appel',
                    'saisi_par' => $_SESSION['user_id']
                ]);
                $count++;
            }
        }
    }

    Security::logActivity('appel', "Appel effectué: $count absences enregistrées");
    echo json_encode(['success' => true, 'absences_enregistrees' => $count]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Action invalide']);
