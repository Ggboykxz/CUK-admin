<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use CUK\Security;

Security::initSession();
Security::requireAuth();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// Élèves
$etudiants = db()->fetchAll(
    "SELECT id, numero, nom, prenom, email, telephone FROM etudiants
     WHERE nom LIKE :q OR prenom LIKE :q2 OR numero LIKE :q3 OR email LIKE :q4
     LIMIT 5",
    ['q' => "%{$q}%", 'q2' => "%{$q}%", 'q3' => "%{$q}%", 'q4' => "%{$q}%"]
);
foreach ($etudiants as $e) {
    $results[] = [
        'type' => 'Étudiant',
        'url' => '?page=etudiants',
        'label' => $e['prenom'] . ' ' . $e['nom'] . ' (' . $e['numero'] . ')',
        'sub' => $e['email'] ?: $e['telephone'] ?: ''
    ];
}

// Utilisateurs
if (in_array($_SESSION['user_role'] ?? '', ['root', 'administrateur'], true)) {
    $users = db()->fetchAll(
        "SELECT id, username, nom, prenom, email FROM users
         WHERE nom LIKE :q OR prenom LIKE :q2 OR username LIKE :q3 OR email LIKE :q4
         LIMIT 5",
        ['q' => "%{$q}%", 'q2' => "%{$q}%", 'q3' => "%{$q}%", 'q4' => "%{$q}%"]
    );
    foreach ($users as $u) {
        $results[] = [
            'type' => 'Utilisateur',
            'url' => '?page=utilisateurs',
            'label' => $u['prenom'] . ' ' . $u['nom'] . ' (@' . $u['username'] . ')',
            'sub' => $u['email'] ?: ''
        ];
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
