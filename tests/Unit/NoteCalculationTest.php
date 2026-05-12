<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NoteCalculationTest extends TestCase
{
    public function testMoyenneEcAvecTousCoefficients(): void
    {
        $cc = 14.0;
        $tp = 12.0;
        $examen = 15.0;
        $coefCc = 0.30;
        $coefTp = 0.20;
        $coefExamen = 0.50;

        $moyenne = ($cc * $coefCc + $tp * $coefTp + $examen * $coefExamen) / ($coefCc + $coefTp + $coefExamen);

        $this->assertEqualsWithDelta(14.2, $moyenne, 0.01);
    }

    public function testMoyenneEcCcEtExamenSeulement(): void
    {
        $cc = 14.0;
        $examen = 16.0;

        $moyenne = $cc * 0.30 + $examen * 0.70;

        $this->assertEqualsWithDelta(15.4, $moyenne, 0.01);
    }

    public function testMoyenneEcExamenSeulement(): void
    {
        $examen = 15.0;
        $moyenne = $examen;

        $this->assertEqualsWithDelta(15.0, $moyenne, 0.01);
    }

    public function testMoyenneEcAvecCoefficientsPersonnalises(): void
    {
        $cc = 10.0;
        $tp = 12.0;
        $examen = 14.0;
        $coefCc = 0.40;
        $coefTp = 0.10;
        $coefExamen = 0.50;

        $total = $cc * $coefCc + $tp * $coefTp + $examen * $coefExamen;
        $sommeCoef = $coefCc + $coefTp + $coefExamen;
        $moyenne = $total / $sommeCoef;

        $this->assertEqualsWithDelta(12.6, $moyenne, 0.01);
    }

    public function testMoyenneUe(): void
    {
        $ecs = [
            ['moyenne' => 14.0, 'coef' => 3],
            ['moyenne' => 12.0, 'coef' => 2],
            ['moyenne' => 15.0, 'coef' => 1],
        ];

        $total = 0;
        $totalCoef = 0;
        foreach ($ecs as $ec) {
            $total += $ec['moyenne'] * $ec['coef'];
            $totalCoef += $ec['coef'];
        }
        $moyenneUe = $total / $totalCoef;

        $this->assertEqualsWithDelta(13.67, $moyenneUe, 0.01);
    }

    public function testValidationUe(): void
    {
        $this->assertTrue(10.0 >= 10);
        $this->assertTrue(12.5 >= 10);
        $this->assertFalse(9.99 >= 10);
        $this->assertFalse(8.0 >= 10);
    }

    public function testMention(): void
    {
        $this->assertEquals('Excellent', $this->getMention(18.0));
        $this->assertEquals('Très Bien', $this->getMention(16.5));
        $this->assertEquals('Bien', $this->getMention(14.5));
        $this->assertEquals('Assez Bien', $this->getMention(12.5));
        $this->assertEquals('Passable', $this->getMention(10.5));
        $this->assertEquals('Ajourné', $this->getMention(9.5));
        $this->assertEquals('-', $this->getMention(null));
    }

    public function testDecision(): void
    {
        $this->assertEquals('admis', $this->getDecision(10.0, true));
        $this->assertEquals('admis', $this->getDecision(15.0, true));
        $this->assertEquals('ajourne', $this->getDecision(9.5, true));
        $this->assertEquals('en_attente', $this->getDecision(null, false));
        $this->assertEquals('en_attente', $this->getDecision(null, true));
    }

    public function testValidationSemestre(): void
    {
        $ues = [
            ['moyenne' => 12.0, 'credits' => 6],
            ['moyenne' => 10.5, 'credits' => 6],
            ['moyenne' => 8.0, 'credits' => 4],
            ['moyenne' => 14.0, 'credits' => 4],
            ['moyenne' => 11.0, 'credits' => 2],
        ];

        $totalPondere = 0;
        $totalCredits = 0;
        $creditsObtenus = 0;

        foreach ($ues as $ue) {
            $totalPondere += $ue['moyenne'] * $ue['credits'];
            $totalCredits += $ue['credits'];
            if ($ue['moyenne'] >= 10) {
                $creditsObtenus += $ue['credits'];
            }
        }

        $moyenneSemestre = $totalPondere / $totalCredits;

        $this->assertEqualsWithDelta(11.05, $moyenneSemestre, 0.01);
        $this->assertEquals(22, $totalCredits);
        $this->assertEquals(18, $creditsObtenus);
    }

    private function getMention(?float $moyenne): string
    {
        if ($moyenne === null) return '-';
        if ($moyenne >= 18) return 'Excellent';
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Ajourné';
    }

    private function getDecision(?float $moyenne, bool $hasNotes): string
    {
        if (!$hasNotes || $moyenne === null) return 'en_attente';
        return $moyenne >= 10 ? 'admis' : 'ajourne';
    }
}
