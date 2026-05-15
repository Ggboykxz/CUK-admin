<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use CUK\Security;

Security::initSession();
Security::requireAuth();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$type = $_GET['type'] ?? '';
$filiereId = (int)($_GET['filiere_id'] ?? 0);

switch ($type) {
    case 'etudiants':
        exportEtudiantsExcel($filiereId);
        break;
    case 'notes':
        exportNotesExcel($filiereId);
        break;
    default:
        http_response_code(404);
        echo 'Type inconnu';
        exit;
}

function exportEtudiantsExcel(int $filiereId): void
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Étudiants');

    $headers = ['N°', 'Nom', 'Prénom', 'Sexe', 'Date Naissance', 'Nationalité', 'Téléphone', 'Email', 'Institut', 'Filière', 'Semestre', 'Statut', 'Boursier'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A5F');
        $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }

    $etudiants = $filiereId
        ? db()->fetchAll("SELECT e.*, f.nom as filiere, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id WHERE e.filiere_id = ? ORDER BY e.nom", [$filiereId])
        : db()->fetchAll("SELECT e.*, f.nom as filiere, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id ORDER BY i.sigle, f.nom, e.nom");

    $row = 2;
    foreach ($etudiants as $e) {
        $sheet->setCellValue('A' . $row, $e['numero'] ?? '');
        $sheet->setCellValue('B' . $row, $e['nom']);
        $sheet->setCellValue('C' . $row, $e['prenom']);
        $sheet->setCellValue('D' . $row, $e['sexe']);
        $sheet->setCellValue('E' . $row, $e['date_naissance']);
        $sheet->setCellValue('F' . $row, $e['nationalite']);
        $sheet->setCellValue('G' . $row, $e['telephone'] ?? '');
        $sheet->setCellValue('H' . $row, $e['email'] ?? '');
        $sheet->setCellValue('I' . $row, $e['institut'] ?? '');
        $sheet->setCellValue('J' . $row, $e['filiere'] ?? '');
        $sheet->setCellValue('K' . $row, $e['semestre']);
        $sheet->setCellValue('L' . $row, $e['statut']);
        $sheet->setCellValue('M' . $row, $e['boursier'] ? 'Oui' : 'Non');
        $row++;
    }

    foreach (range('A', 'M') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="etudiants.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function exportNotesExcel(int $filiereId): void
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Notes');

    $headers = ['N°', 'Nom', 'Prénom', 'Filière', 'Semestre', 'EC', 'UE', 'CC', 'TP', 'Examen', 'Moyenne'];
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '1', $h);
        $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A5F');
        $sheet->getStyle($col . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $col++;
    }

    $notes = db()->fetchAll(
        "SELECT n.*, e.numero, e.nom, e.prenom, e.semestre, f.nom as filiere,
                ec.code as ec_code, ec.nom as ec_nom, ue.nom as ue_nom
         FROM notes n
         JOIN etudiants e ON n.etudiant_id = e.id
         JOIN filieres f ON e.filiere_id = f.id
         JOIN ecs ec ON n.ec_id = ec.id
         JOIN ues ue ON ec.ue_id = ue.id
         WHERE e.filiere_id = ?
         ORDER BY e.nom, ec.code", [$filiereId]
    );

    $row = 2;
    foreach ($notes as $n) {
        $sheet->setCellValue('A' . $row, $n['numero'] ?? '');
        $sheet->setCellValue('B' . $row, $n['nom']);
        $sheet->setCellValue('C' . $row, $n['prenom']);
        $sheet->setCellValue('D' . $row, $n['filiere']);
        $sheet->setCellValue('E' . $row, $n['semestre']);
        $sheet->setCellValue('F' . $row, $n['ec_code'] . ' - ' . $n['ec_nom']);
        $sheet->setCellValue('G' . $row, $n['ue_nom']);
        $sheet->setCellValue('H' . $row, $n['cc'] ?? '');
        $sheet->setCellValue('I' . $row, $n['tp'] ?? '');
        $sheet->setCellValue('J' . $row, $n['examen'] ?? '');
        $sheet->setCellValue('K' . $row, $n['moyenne_ec'] ?? '');
        $row++;
    }

    foreach (range('A', 'K') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="notes.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
