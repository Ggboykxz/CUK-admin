<?php

declare(strict_types=1);

namespace CUK\Controllers;

use CUK\Security;

class EtudiantController
{
    public function search(): void
    {
        Security::requireAuth();
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) {
            echo json_encode([]);
            return;
        }

        $results = db()->fetchAll(
            "SELECT e.id, e.numero, e.nom, e.prenom, e.email, e.telephone,
                    f.nom as filiere, i.sigle as institut
             FROM etudiants e
             JOIN filieres f ON e.filiere_id = f.id
             JOIN instituts i ON f.institut_id = i.id
             WHERE e.nom LIKE :q OR e.prenom LIKE :q2 OR e.numero LIKE :q3
                OR e.email LIKE :q4 OR e.telephone LIKE :q5
             ORDER BY e.nom LIMIT 20",
            [
                'q' => "%{$q}%", 'q2' => "%{$q}%", 'q3' => "%{$q}%",
                'q4' => "%{$q}%", 'q5' => "%{$q}%"
            ]
        );

        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function profile(int $id): void
    {
        Security::requireAuth();
        header('Content-Type: application/json');

        $etudiant = db()->fetch(
            "SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut, i.nom as institut_nom
             FROM etudiants e
             JOIN filieres f ON e.filiere_id = f.id
             JOIN instituts i ON f.institut_id = i.id
             WHERE e.id = ?", [$id]
        );

        if (!$etudiant) {
            echo json_encode(['error' => 'Étudiant non trouvé']);
            return;
        }

        $notes = db()->fetchAll(
            "SELECT n.*, ec.code as ec_code, ec.nom as ec_nom, ue.nom as ue_nom
             FROM notes n
             JOIN ecs ec ON n.ec_id = ec.id
             JOIN ues ue ON ec.ue_id = ue.id
             WHERE n.etudiant_id = ?
             ORDER BY ue.nom, ec.code", [$id]
        );

        $absences = db()->fetchAll(
            "SELECT a.*, ec.nom as matiere
             FROM absences a
             LEFT JOIN ecs ec ON a.ec_id = ec.id
             WHERE a.etudiant_id = ?
             ORDER BY a.date_absence DESC LIMIT 20", [$id]
        );

        $incidents = db()->fetchAll(
            "SELECT i.*, u.nom as signaleur
             FROM incidents i
             JOIN users u ON i.utilisateur_id = u.id
             WHERE i.etudiant_id = ?
             ORDER BY i.date_incident DESC LIMIT 20", [$id]
        );

        $moyennes = db()->fetchAll(
            "SELECT ms.*, s.nom as semestre_nom
             FROM moyennes_semestrielles ms
             JOIN semestres s ON ms.semestre_id = s.id
             WHERE ms.etudiant_id = ?
             ORDER BY s.numero", [$id]
        );

        echo json_encode([
            'etudiant' => $etudiant,
            'notes' => $notes,
            'absences' => $absences,
            'incidents' => $incidents,
            'moyennes' => $moyennes
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function importCsv(): void
    {
        Security::requireAuth();
        Security::requireRole('root', 'administrateur');

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Session expirée';
            header('Location: ?page=etudiants');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Erreur lors du téléchargement du fichier';
            header('Location: ?page=etudiants');
            exit;
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            $_SESSION['error'] = 'Impossible de lire le fichier';
            header('Location: ?page=etudiants');
            exit;
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            $_SESSION['error'] = 'Fichier CSV invalide';
            header('Location: ?page=etudiants');
            exit;
        }

        $anneeCourante = db()->fetch("SELECT id FROM annees_academiques WHERE courante = 1");
        $anneeId = $anneeCourante['id'] ?? 0;
        $count = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $data = array_combine($header, $row);
            if (!$data || empty($data['nom']) || empty($data['prenom'])) {
                continue;
            }

            try {
                $filiere = db()->fetch("SELECT id FROM filieres WHERE code = ?", [trim($data['filiere_code'] ?? '')]);
                $filiereId = $filiere['id'] ?? 0;

                if (!$filiereId) {
                    $errors[] = "Ligne " . ($count + 2) . ": Filière non trouvée ({$data['filiere_code']})";
                    continue;
                }

                $numero = 'ETU-' . date('Y') . '-' . str_pad((db()->fetch("SELECT COUNT(*)+1 as c FROM etudiants")['c'] ?? 1), 3, '0', STR_PAD_LEFT);
                $matricule = 'MAT-' . date('Y') . '-' . substr(md5(uniqid('', true)), 0, 6);

                db()->insert('etudiants', [
                    'numero' => $numero,
                    'matricule' => $matricule,
                    'nom' => trim($data['nom']),
                    'prenom' => trim($data['prenom']),
                    'sexe' => Security::validateEnum(strtoupper(trim($data['sexe'] ?? 'M')), ['M', 'F'], 'M'),
                    'date_naissance' => trim($data['date_naissance'] ?? date('Y-m-d')),
                    'lieu_naissance' => trim($data['lieu_naissance'] ?? ''),
                    'nationalite' => trim($data['nationalite'] ?? 'Gabonaise'),
                    'telephone' => trim($data['telephone'] ?? ''),
                    'email' => trim($data['email'] ?? ''),
                    'adresse' => trim($data['adresse'] ?? ''),
                    'filiere_id' => $filiereId,
                    'semestre' => Security::validateEnum(trim($data['semestre'] ?? 'S1'), ['S1', 'S2', 'S3', 'S4'], 'S1'),
                    'annee_academique_id' => $anneeId,
                    'date_inscription' => date('Y-m-d'),
                    'boursier' => isset($data['boursier']) && strtolower($data['boursier']) === 'oui' ? 1 : 0,
                    'statut' => 'actif'
                ]);
                $count++;
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($count + 2) . ": " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($count > 0) {
            Security::logActivity('import_csv', "$count étudiants importés depuis CSV", 'etudiants');
            $_SESSION['success'] = "$count étudiants importés avec succès." . (!empty($errors) ? ' ' . count($errors) . ' erreurs.' : '');
        } else {
            $_SESSION['error'] = 'Aucun étudiant importé. ' . (!empty($errors) ? implode(', ', array_slice($errors, 0, 5)) : '');
        }

        header('Location: ?page=etudiants');
        exit;
    }

    public function uploadPhoto(): void
    {
        Security::requireAuth();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Fichier invalide']);
            exit;
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format non autorisé (jpg, png, gif, webp)']);
            exit;
        }

        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'Fichier trop volumineux (max 2 Mo)']);
            exit;
        }

        $uploadDir = __DIR__ . '/../../uploads/photos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'etu_' . $id . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
            exit;
        }

        db()->update('etudiants', ['photo_path' => 'uploads/photos/' . $filename], 'id = :id', ['id' => $id]);

        echo json_encode(['success' => true, 'path' => 'uploads/photos/' . $filename]);
        exit;
    }
}
