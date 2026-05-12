<?php

declare(strict_types=1);

/**
 * Scheduled Tasks for CUK-Admin
 * Run via cron: * * * * * php /path/to/scheduler/tasks.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "[" . date('Y-m-d H:i:s') . "] CUK-Admin Scheduler - Démarrage\n";

$tasks = [
    'cleanup_old_sessions' => 'cleanupOldSessions',
    'send_absence_alerts' => 'sendAbsenceAlerts',
    'backup_database' => 'backupDatabase',
    'notify_pending_validations' => 'notifyPendingValidations',
];

foreach ($tasks as $name => $func) {
    try {
        echo "  → {$name}... ";
        $func();
        echo "OK\n";
    } catch (\Throwable $e) {
        echo "ERREUR: {$e->getMessage()}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Terminé\n";

function cleanupOldSessions(): void
{
    $logFile = __DIR__ . '/../runtime/scheduler.log';
    $dir = __DIR__ . '/../runtime/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

function sendAbsenceAlerts(): void
{
    $seuil = 10;
    $etudiants = db()->fetchAll(
        "SELECT e.id, e.nom, e.prenom, e.numero, COUNT(a.id) as total_absences
         FROM etudiants e
         JOIN absences a ON e.id = a.etudiant_id
         WHERE a.justifiee = 0 AND a.annee_academique_id = (SELECT id FROM annees_academiques WHERE courante = 1)
         GROUP BY e.id
         HAVING total_absences >= ?
         ORDER BY total_absences DESC", [$seuil]
    );

    foreach ($etudiants as $e) {
        $admins = db()->fetchAll("SELECT id FROM users WHERE role IN ('root', 'administrateur') AND actif = 1");
        foreach ($admins as $admin) {
            $existing = db()->fetch(
                "SELECT id FROM notifications WHERE user_id = ? AND titre LIKE '%alerte%' AND message LIKE ? AND lu = 0",
                [$admin['id'], "%{$e['numero']}%"]
            );
            if (!$existing) {
                db()->insert('notifications', [
                    'user_id' => $admin['id'],
                    'type' => 'warning',
                    'titre' => 'Alerte absences',
                    'message' => "{$e['prenom']} {$e['nom']} ({$e['numero']}) : {$e['total_absences']} absences non justifiées",
                    'lien' => '?page=absences'
                ]);
            }
        }
    }

    if (!empty($etudiants)) {
        echo count($etudiants) . " alertes envoyées. ";
    }
}

function backupDatabase(): void
{
    $config = require __DIR__ . '/../config/database.php';
    $backupDir = __DIR__ . '/../database/backups/';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    $lastBackup = db()->fetch("SELECT MAX(created_at) as last FROM journal_activite WHERE action = 'backup_auto'");
    if ($lastBackup && $lastBackup['last']) {
        $diff = time() - strtotime($lastBackup['last']);
        if ($diff < 86400) { // 24h
            return;
        }
    }

    $filename = 'auto_backup_' . date('Y-m-d') . '.sqlite';
    $dbPath = __DIR__ . '/../' . ($config['database'] ?? 'database/cuk_admin.sqlite');
    if (file_exists($dbPath)) {
        copy($dbPath, $backupDir . $filename);
        $count = count(glob($backupDir . 'auto_backup_*.sqlite'));
        if ($count > 7) {
            $old = glob($backupDir . 'auto_backup_*.sqlite');
            sort($old);
            $toDelete = array_slice($old, 0, $count - 7);
            foreach ($toDelete as $f) unlink($f);
        }
        db()->insert('journal_activite', [
            'user_id' => null,
            'action' => 'backup_auto',
            'details' => "Backup automatique: {$filename}",
        ]);
        echo "Backup créé: {$filename}. ";
    }
}

function notifyPendingValidations(): void
{
    $pending = db()->fetchAll(
        "SELECT COUNT(*) as total FROM notes WHERE valide = 0 AND annee_academique_id = (SELECT id FROM annees_academiques WHERE courante = 1)"
    );
    $total = $pending[0]['total'] ?? 0;

    if ($total > 0) {
        $admins = db()->fetchAll("SELECT id FROM users WHERE role IN ('root', 'administrateur') AND actif = 1");
        foreach ($admins as $admin) {
            $existing = db()->fetch(
                "SELECT id FROM notifications WHERE user_id = ? AND titre = 'Notes en attente' AND lu = 0",
                [$admin['id']]
            );
            if (!$existing) {
                db()->insert('notifications', [
                    'user_id' => $admin['id'],
                    'type' => 'info',
                    'titre' => 'Notes en attente',
                    'message' => "{$total} note(s) en attente de validation",
                    'lien' => '?page=notes'
                ]);
            }
        }
    }
}
