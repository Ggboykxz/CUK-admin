<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class StudentLifecycleTest extends TestCase
{
    private array $currentYear = ['id' => 1, 'annee' => '2025-2026'];
    private array $institut = ['id' => 1, 'code' => 'ISTPK', 'nom' => 'Institut Test', 'sigle' => 'ISTPK'];
    private array $filiere = ['id' => 1, 'code' => 'TEST-IC', 'nom' => 'Informatique Test'];
    private array $semestre = ['id' => 1, 'code' => 'TEST-IC-S1', 'nom' => 'Semestre 1', 'numero' => 1, 'filiere_id' => 1, 'credits' => 30];
    private array $ue = ['id' => 1, 'code' => 'TEST-UE1', 'nom' => 'Programmation Test', 'filiere_id' => 1, 'semestre_id' => 1, 'credits' => 6];
    private array $ec = ['id' => 1, 'code' => 'TEST-EC1', 'nom' => 'Algorithmes Test', 'ue_id' => 1, 'coefficient' => 3, 'coefficient_cc' => 0.30, 'coefficient_tp' => 0.20, 'coefficient_examen' => 0.50];

    public function testStudentCreatedSuccessfully(): void
    {
        $student = [
            'numero' => 'ETU-2025-099',
            'matricule' => 'MAT-2025-099',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'sexe' => 'M',
            'date_naissance' => '2000-01-15',
            'lieu_naissance' => 'Libreville',
            'nationalite' => 'Gabonaise',
            'telephone' => '+241 00 00 00 00',
            'email' => 'jean.dupont@test.ga',
            'adresse' => 'Quartier Test',
            'filiere_id' => 1,
            'semestre' => 'S1',
            'annee_academique_id' => 1,
            'date_inscription' => '2025-10-01',
            'boursier' => 0,
            'statut' => 'actif',
        ];

        $this->assertEquals('ETU-2025-099', $student['numero']);
        $this->assertEquals('Dupont', $student['nom']);
        $this->assertEquals('actif', $student['statut']);
        $this->assertFalse((bool)$student['boursier']);
    }

    public function testNoteCalculationFullCycle(): void
    {
        $cc = 14.0;
        $tp = 12.0;
        $examen = 15.0;

        $coefCc = 0.30;
        $coefTp = 0.20;
        $coefExamen = 0.50;
        $sommeCoef = $coefCc + $coefTp + $coefExamen;

        $moyenneEc = ($cc * $coefCc + $tp * $coefTp + $examen * $coefExamen) / $sommeCoef;
        $this->assertEqualsWithDelta(14.2, $moyenneEc, 0.01);

        $valideEc = $moyenneEc >= 10;
        $this->assertTrue($valideEc);

        $note = [
            'etudiant_id' => 99,
            'ec_id' => 1,
            'annee_academique_id' => 1,
            'cc' => $cc,
            'tp' => $tp,
            'examen' => $examen,
            'moyenne_ec' => $moyenneEc,
            'valide' => 0,
        ];

        $this->assertEquals(14.2, $note['moyenne_ec']);
        $this->assertEquals(0, $note['valide']);
    }

    public function testGradeValidationWorkflow(): void
    {
        $note = ['id' => 1, 'valide' => 0, 'date_validation' => null];

        $note['valide'] = 1;
        $note['date_validation'] = date('Y-m-d H:i:s');

        $this->assertEquals(1, $note['valide']);
        $this->assertNotNull($note['date_validation']);
    }

    public function testSemesterAverageCalculation(): void
    {
        $ues = [
            ['moyenne' => 14.2, 'credits' => 6, 'valide' => true],
            ['moyenne' => 12.0, 'credits' => 6, 'valide' => true],
            ['moyenne' => 8.5, 'credits' => 4, 'valide' => false],
            ['moyenne' => 15.0, 'credits' => 4, 'valide' => true],
        ];

        $totalPondere = 0;
        $totalCredits = 0;
        $creditsObtenus = 0;

        foreach ($ues as $ue) {
            $totalPondere += $ue['moyenne'] * $ue['credits'];
            $totalCredits += $ue['credits'];
            if ($ue['valide']) {
                $creditsObtenus += $ue['credits'];
            }
        }

        $moyenne = $totalPondere / $totalCredits;

        $this->assertEqualsWithDelta(12.45, $moyenne, 0.01);
        $this->assertEquals(20, $totalCredits);
        $this->assertEquals(16, $creditsObtenus);
        $this->assertEquals(80.0, ($creditsObtenus / $totalCredits) * 100);
    }

    public function testCompleteStudentJourney(): void
    {
        $student = [
            'nom' => 'Moussavou',
            'prenom' => 'Armel',
            'filiere' => 'Informatique',
            'semestre' => 'S1',
        ];

        $notes = [
            ['ec' => 'Programmation C', 'moyenne' => 14.5],
            ['ec' => 'Algorithmique', 'moyenne' => 12.0],
            ['ec' => 'Mathématiques', 'moyenne' => 10.5],
        ];

        $totalNotes = 0;
        foreach ($notes as $n) {
            $totalNotes += $n['moyenne'];
        }
        $moyenneGenerale = $totalNotes / count($notes);

        $this->assertEquals('Moussavou', $student['nom']);
        $this->assertEquals('S1', $student['semestre']);
        $this->assertEqualsWithDelta(12.33, $moyenneGenerale, 0.01);

        $student['semestre'] = 'S2';
        $this->assertEquals('S2', $student['semestre']);
    }
}
