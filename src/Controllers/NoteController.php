<?php

declare(strict_types=1);

namespace CUK\Controllers;

use CUK\Security;

class NoteController
{
    public function saveSemesterAverage(): void
    {
        Security::requireAuth();
        header('Content-Type: application/json');

        $etudiantId = (int)($_POST['etudiant_id'] ?? 0);
        $semestre = Security::validateEnum($_POST['semestre'] ?? '', ['S1', 'S2', 'S3', 'S4'], 'S1');
        $filiereId = (int)($_POST['filiere_id'] ?? 0);
        $anneeId = (int)($_POST['annee_academique_id'] ?? 0);

        $semestreData = db()->fetch("SELECT * FROM semestres WHERE code LIKE ? AND filiere_id = ?", [$filiereId . '-' . $semestre . '%', $filiereId]);
        if (!$semestreData) {
            echo json_encode(['success' => false, 'error' => 'Semestre non trouvé']);
            exit;
        }

        $ues = db()->fetchAll("SELECT * FROM ues WHERE semestre_id = ? AND active = 1", [$semestreData['id']]);
        $totalMoyenne = 0;
        $totalCreditsUE = 0;
        $creditsObtenus = 0;

        foreach ($ues as $ue) {
            $ecs = db()->fetchAll("SELECT * FROM ecs WHERE ue_id = ? AND active = 1", [$ue['id']]);
            $moyenneUe = 0;
            $totalCoefUe = 0;

            foreach ($ecs as $ec) {
                $note = db()->fetch("SELECT * FROM notes WHERE etudiant_id = ? AND ec_id = ? AND annee_academique_id = ?", [$etudiantId, $ec['id'], $anneeId]);
                if ($note && $note['moyenne_ec'] !== null) {
                    $moyenneUe += floatval($note['moyenne_ec']) * floatval($ec['coefficient']);
                    $totalCoefUe += floatval($ec['coefficient']);
                }
            }

            $moyenneUeFinale = $totalCoefUe > 0 ? round($moyenneUe / $totalCoefUe, 2) : null;
            $valide = $moyenneUeFinale !== null && $moyenneUeFinale >= 10;

            if ($moyenneUeFinale !== null) {
                $totalMoyenne += $moyenneUe;
                $totalCreditsUE += floatval($ue['credits']);
                $creditsObtenus += $valide ? floatval($ue['credits']) : 0;
            }
        }

        $moyenneSemestre = $totalCreditsUE > 0 ? round($totalMoyenne / $totalCreditsUE, 2) : null;
        $validation = $moyenneSemestre !== null && $moyenneSemestre >= 10 ? 'valide' : 'ajourne';
        $mention = $this->getMention($moyenneSemestre);

        $existing = db()->fetch(
            "SELECT id FROM moyennes_semestrielles WHERE etudiant_id = ? AND semestre_id = ? AND annee_academique_id = ?",
            [$etudiantId, $semestreData['id'], $anneeId]
        );

        $data = [
            'etudiant_id' => $etudiantId,
            'semestre_id' => $semestreData['id'],
            'annee_academique_id' => $anneeId,
            'moyenne_semestre' => $moyenneSemestre,
            'credits_obtenus' => $creditsObtenus,
            'total_credits' => $totalCreditsUE,
            'validation' => $validation,
            'mention_semestre' => $mention
        ];

        if ($existing) {
            db()->update('moyennes_semestrielles', $data, 'id = :id', ['id' => $existing['id']]);
        } else {
            db()->insert('moyennes_semestrielles', $data);
        }

        echo json_encode([
            'success' => true,
            'moyenne_semestre' => $moyenneSemestre,
            'credits_obtenus' => $creditsObtenus,
            'total_credits' => $totalCreditsUE,
            'validation' => $validation,
            'mention' => $mention
        ]);
        exit;
    }

    public function validateGrade(): void
    {
        Security::requireAuth();
        Security::requireRole('root', 'administrateur');

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Session expirée']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        db()->update('notes', [
            'valide' => 1,
            'date_validation' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    public function getMention(?float $moyenne): string
    {
        if ($moyenne === null) return '-';
        if ($moyenne >= 18) return 'Excellent';
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Ajourné';
    }

    public function getDecision(?float $moyenne): string
    {
        if ($moyenne === null) return 'en_attente';
        return $moyenne >= 10 ? 'admis' : 'ajourne';
    }
}
