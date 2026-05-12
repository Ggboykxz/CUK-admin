<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;

Security::initSession();
Security::requireAuth();

use Dompdf\Dompdf;
use Dompdf\Options;

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

switch ($type) {
    case 'releve':
        exportReleveNotes($id);
        break;
    case 'bulletin':
        exportBulletin($id);
        break;
    case 'liste':
        exportListeEtudiants();
        break;
    default:
        http_response_code(404);
        echo 'Type d\'export inconnu';
        exit;
}

function getHtmlReleve(int $etudiantId): string
{
    $etudiant = db()->fetch(
        "SELECT e.*, f.nom as filiere, f.code as filiere_code, i.sigle as institut
         FROM etudiants e
         JOIN filieres f ON e.filiere_id = f.id
         JOIN instituts i ON f.institut_id = i.id
         WHERE e.id = ?", [$etudiantId]
    );

    if (!$etudiant) return '<p>Étudiant non trouvé</p>';

    $notes = db()->fetchAll(
        "SELECT n.*, ec.code as ec_code, ec.nom as ec_nom, ec.coefficient,
                ue.nom as ue_nom, ue.code as ue_code
         FROM notes n
         JOIN ecs ec ON n.ec_id = ec.id
         JOIN ues ue ON ec.ue_id = ue.id
         WHERE n.etudiant_id = ?
         ORDER BY ue.nom, ec.code", [$etudiantId]
    );

    $html = '
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; }
        .header h1 { color: #1e3a5f; font-size: 16px; margin: 0; }
        .header h2 { font-size: 13px; margin: 5px 0; }
        .info { margin-bottom: 15px; }
        .info td { padding: 2px 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #1e3a5f; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .ue-row { background: #f0f4ff; font-weight: bold; }
        .total { font-weight: bold; border-top: 2px solid #333; }
        .footer { text-align: center; margin-top: 30px; font-size: 9px; color: #666; }
    </style>
    <div class="header">
        <h1>RELEVÉ DE NOTES</h1>
        <h2>' . htmlspecialchars($etudiant['institut'] ?? '') . '</h2>
        <p>' . htmlspecialchars($etudiant['filiere'] ?? '') . '</p>
    </div>
    <table class="info">
        <tr><td><strong>Nom:</strong></td><td>' . htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) . '</td>
            <td><strong>N° Étudiant:</strong></td><td>' . htmlspecialchars($etudiant['numero'] ?? '') . '</td></tr>
        <tr><td><strong>Semestre:</strong></td><td>' . htmlspecialchars($etudiant['semestre'] ?? '') . '</td>
            <td><strong>Année:</strong></td><td>' . date('Y') . '</td></tr>
    </table>
    <table>
        <thead><tr><th>UE</th><th>EC</th><th>Coef.</th><th>CC</th><th>TP</th><th>Examen</th><th>Moy.</th></tr></thead>
        <tbody>';

    $currentUe = '';
    foreach ($notes as $n) {
        if ($currentUe !== $n['ue_nom']) {
            $currentUe = $n['ue_nom'];
            $html .= '<tr class="ue-row"><td colspan="7">' . htmlspecialchars($n['ue_code'] . ' - ' . $n['ue_nom']) . '</td></tr>';
        }
        $html .= '<tr>
            <td></td>
            <td>' . htmlspecialchars($n['ec_code']) . ' - ' . htmlspecialchars($n['ec_nom']) . '</td>
            <td>' . number_format((float)$n['coefficient'], 1) . '</td>
            <td>' . ($n['cc'] !== null ? number_format((float)$n['cc'], 2) : '-') . '</td>
            <td>' . ($n['tp'] !== null ? number_format((float)$n['tp'], 2) : '-') . '</td>
            <td>' . ($n['examen'] !== null ? number_format((float)$n['examen'], 2) : '-') . '</td>
            <td><strong>' . ($n['moyenne_ec'] !== null ? number_format((float)$n['moyenne_ec'], 2) : '-') . '</strong></td>
        </tr>';
    }

    $html .= '</tbody></table>
    <div class="footer">
        <p>Relevé généré le ' . date('d/m/Y') . ' - CUK-Admin v1.0</p>
    </div>';

    return $html;
}

function exportReleveNotes(int $etudiantId): void
{
    $html = getHtmlReleve($etudiantId);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("releve_notes_{$etudiantId}.pdf", ['Attachment' => true]);
    exit;
}

function exportBulletin(int $etudiantId): void
{
    $html = getHtmlReleve($etudiantId);
    $html = str_replace('RELEVÉ DE NOTES', 'BULLETIN DE NOTES', $html);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("bulletin_{$etudiantId}.pdf", ['Attachment' => true]);
    exit;
}

function exportListeEtudiants(): void
{
    $filiereId = (int)($_GET['filiere_id'] ?? 0);

    $etudiants = $filiereId
        ? db()->fetchAll("SELECT e.*, f.nom as filiere, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id WHERE e.filiere_id = ? ORDER BY e.nom", [$filiereId])
        : db()->fetchAll("SELECT e.*, f.nom as filiere, i.sigle as institut FROM etudiants e JOIN filieres f ON e.filiere_id = f.id JOIN instituts i ON f.institut_id = i.id ORDER BY i.sigle, f.nom, e.nom");

    $html = '
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; }
        .header h1 { color: #1e3a5f; font-size: 16px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e3a5f; color: white; padding: 6px; text-align: left; font-size: 9px; }
        td { padding: 4px 6px; border-bottom: 1px solid #ddd; }
    </style>
    <div class="header">
        <h1>LISTE DES ÉTUDIANTS</h1>
        <p>Année ' . date('Y') . '</p>
    </div>
    <table>
        <thead><tr><th>N°</th><th>Nom</th><th>Prénom</th><th>Institut</th><th>Filière</th><th>Semestre</th><th>Statut</th></tr></thead>
        <tbody>';
    foreach ($etudiants as $i => $e) {
        $html .= '<tr>
            <td>' . htmlspecialchars($e['numero'] ?? '') . '</td>
            <td>' . htmlspecialchars($e['nom']) . '</td>
            <td>' . htmlspecialchars($e['prenom']) . '</td>
            <td>' . htmlspecialchars($e['institut'] ?? '') . '</td>
            <td>' . htmlspecialchars($e['filiere'] ?? '') . '</td>
            <td>' . htmlspecialchars($e['semestre'] ?? '') . '</td>
            <td>' . htmlspecialchars($e['statut'] ?? '') . '</td>
        </tr>';
    }
    $html .= '</tbody></table>
        <p style="text-align:right;margin-top:10px;font-size:9px;color:#666;">Total: ' . count($etudiants) . ' étudiants</p>';

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("liste_etudiants.pdf", ['Attachment' => true]);
    exit;
}
