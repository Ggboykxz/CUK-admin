<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/rest.php', '', $path);
$path = '/' . trim($path, '/');

$body = json_decode(file_get_contents('php://input'), true) ?? [];

function auth(): array
{
    Security::initSession();
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
    return $_SESSION;
}

function json(mixed $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $session = auth();

    switch (true) {
        // GET /etudiants - list all
        case $method === 'GET' && preg_match('#^/etudiants/?$#', $path):
            $etudiants = db()->fetchAll("SELECT e.id, e.numero, e.nom, e.prenom, e.sexe, e.statut, e.semestre, f.nom as filiere, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id ORDER BY e.nom");
            json($etudiants);
            break;

        // GET /etudiants/{id}
        case $method === 'GET' && preg_match('#^/etudiants/(\d+)$#', $path, $m):
            $e = db()->fetch("SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id WHERE e.id = ?", [(int)$m[1]]);
            json($e ?: ['error' => 'Not found'], $e ? 200 : 404);
            break;

        // POST /etudiants - create
        case $method === 'POST' && preg_match('#^/etudiants/?$#', $path):
            $annee = db()->fetch("SELECT id FROM annees_academiques WHERE courante = 1");
            $numero = 'ETU-' . date('Y') . '-' . str_pad((db()->fetch("SELECT COUNT(*)+1 as c FROM etudiants")['c'] ?? 1), 3, '0', STR_PAD_LEFT);
            $matricule = 'MAT-' . date('Y') . '-' . substr(md5(uniqid('', true)), 0, 6);
            $id = db()->insert('etudiants', [
                'numero' => $numero,
                'matricule' => $matricule,
                'nom' => $body['nom'] ?? '',
                'prenom' => $body['prenom'] ?? '',
                'sexe' => in_array($body['sexe'] ?? '', ['M', 'F']) ? $body['sexe'] : 'M',
                'date_naissance' => $body['date_naissance'] ?? date('Y-m-d'),
                'filiere_id' => (int)($body['filiere_id'] ?? 0),
                'semestre' => in_array($body['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4']) ? $body['semestre'] : 'S1',
                'annee_academique_id' => (int)($annee['id'] ?? 0),
                'date_inscription' => date('Y-m-d'),
                'statut' => 'actif',
            ]);
            json(['id' => $id, 'numero' => $numero, 'matricule' => $matricule], 201);
            break;

        // PUT /etudiants/{id}
        case $method === 'PUT' && preg_match('#^/etudiants/(\d+)$#', $path, $m):
            db()->update('etudiants', $body, 'id = :id', ['id' => (int)$m[1]]);
            json(['success' => true]);
            break;

        // DELETE /etudiants/{id}
        case $method === 'DELETE' && preg_match('#^/etudiants/(\d+)$#', $path, $m):
            db()->delete('etudiants', 'id = :id', ['id' => (int)$m[1]]);
            json(['success' => true]);
            break;

        // GET /filieres
        case $method === 'GET' && preg_match('#^/filieres/?$#', $path):
            json(db()->fetchAll("SELECT f.*, i.sigle as institut FROM filieres f JOIN instituts i ON f.institut_id = i.id WHERE f.active = 1"));
            break;

        // GET /notes/{etudiant_id}
        case $method === 'GET' && preg_match('#^/notes/(\d+)$#', $path, $m):
            json(db()->fetchAll("SELECT n.*, ec.code as ec_code, ec.nom as ec_nom, ue.nom as ue_nom FROM notes n JOIN ecs ec ON n.ec_id = ec.id JOIN ues ue ON ec.ue_id = ue.id WHERE n.etudiant_id = ? ORDER BY ue.nom", [(int)$m[1]]));
            break;

        // GET /stats
        case $method === 'GET' && preg_match('#^/stats/?$#', $path):
            json([
                'etudiants' => db()->fetch("SELECT COUNT(*) as c FROM etudiants")['c'],
                'filieres' => db()->fetch("SELECT COUNT(*) as c FROM filieres WHERE active = 1")['c'],
                'utilisateurs' => db()->fetch("SELECT COUNT(*) as c FROM users WHERE actif = 1")['c'],
                'notes' => db()->fetch("SELECT COUNT(*) as c FROM notes")['c'],
                'absences' => db()->fetch("SELECT COUNT(*) as c FROM absences")['c'],
            ]);
            break;

        // GET /search?q=...
        case $method === 'GET' && preg_match('#^/search/?$#', $path):
            $q = $_GET['q'] ?? '';
            if (strlen($q) < 2) json([]);
            json(db()->fetchAll("SELECT id, numero, nom, prenom, 'etudiant' as type FROM etudiants WHERE nom LIKE :q OR prenom LIKE :q2 LIMIT 10", ['q' => "%{$q}%", 'q2' => "%{$q}%"]));
            break;

        default:
            json(['error' => 'Not found', 'path' => $path, 'method' => $method], 404);
    }
} catch (\Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
