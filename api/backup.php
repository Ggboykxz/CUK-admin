<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CUK\Security;

Security::initSession();
Security::requireRole('root', 'administrateur');

$action = $_GET['action'] ?? 'list';
$backupDir = __DIR__ . '/../database/backups/';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

header('Content-Type: application/json');

switch ($action) {
    case 'create':
        try {
            $config = require __DIR__ . '/../config/database.php';
            $filename = 'backup_' . date('Y-m-d_His') . '.sqlite';
            $dest = $backupDir . $filename;

            if (($config['driver'] ?? 'sqlite') === 'sqlite') {
                $dbPath = __DIR__ . '/../' . ($config['database'] ?? 'database/cuk_admin.sqlite');
                if (file_exists($dbPath)) {
                    copy($dbPath, $dest);
                    $stats = sqliteBackup($backupDir, $dest);
                } else {
                    throw new \RuntimeException('Base de données non trouvée');
                }
            } else {
                $stats = mysqlBackup($config, $backupDir, $filename, $dest);
            }

            Security::logActivity('backup', "Backup créé: {$filename}");
            echo json_encode(['success' => true, 'file' => $filename, 'size' => filesize($dest)]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'restore':
        $file = basename($_GET['file'] ?? '');
        if (!$file || !file_exists($backupDir . $file)) {
            http_response_code(404);
            echo json_encode(['error' => 'Fichier non trouvé']);
            exit;
        }

        try {
            $config = require __DIR__ . '/../config/database.php';
            if (($config['driver'] ?? 'sqlite') === 'sqlite') {
                $dbPath = __DIR__ . '/../' . ($config['database'] ?? 'database/cuk_admin.sqlite');
                copy($backupDir . $file, $dbPath);
            }
            Security::logActivity('restore', "Backup restauré: {$file}");
            echo json_encode(['success' => true, 'message' => "Base de données restaurée depuis: {$file}"]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $file = basename($_GET['file'] ?? '');
        $path = $backupDir . $file;
        if (file_exists($path)) {
            unlink($path);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Fichier non trouvé']);
        }
        break;

    case 'list':
    default:
        $files = glob($backupDir . '*.sqlite');
        $backups = [];
        foreach ($files as $f) {
            $backups[] = [
                'file' => basename($f),
                'size' => filesize($f),
                'size_formatted' => formatSize(filesize($f)),
                'date' => date('Y-m-d H:i:s', filemtime($f)),
            ];
        }
        rsort($backups);
        echo json_encode($backups);
        break;
}

function formatSize(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' Mo';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' Ko';
    return $bytes . ' o';
}

function sqliteBackup(string $backupDir, string $dest): array
{
    return ['method' => 'copy', 'dest' => $dest];
}

function mysqlBackup(array $config, string $backupDir, string $filename, string &$dest): array
{
    $dest = $backupDir . str_replace('.sqlite', '.sql', $filename);
    $pdo = new \PDO("mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4", $config['username'] ?? '', $config['password'] ?? '');
    $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
    $sql = "-- Backup: {$filename}\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        $sql .= $create['Create Table'] . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = implode('`, `', array_keys($rows[0]));
            foreach ($rows as $row) {
                $values = implode("', '", array_map(fn($v) => is_null($v) ? 'NULL' : str_replace("'", "''", (string)$v), array_values($row)));
                $sql .= "INSERT INTO `{$table}` (`{$columns}`) VALUES ('{$values}');\n";
            }
            $sql .= "\n";
        }
    }

    file_put_contents($dest, $sql);
    return ['method' => 'mysqldump', 'dest' => $dest];
}
