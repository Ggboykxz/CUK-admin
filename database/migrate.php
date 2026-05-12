#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

echo "========================================\n";
echo "  CUK-Admin - Migration Runner\n";
echo "========================================\n\n";

try {
    $pdo = \CUK\Database::getInstance()->getConnection();
    $migrator = new \CUK\Migrations\Migrator($pdo);

    $command = $argv[1] ?? 'migrate';

    switch ($command) {
        case 'migrate':
            echo "Exécution des migrations...\n";
            $results = $migrator->migrate();
            $count = 0;
            foreach ($results as $r) {
                $status = $r['status'] === 'ok' ? "\033[32mOK\033[0m" : "\033[31mERREUR\033[0m";
                echo "  [{$status}] {$r['migration']}";
                if (!empty($r['error'])) echo " - {$r['error']}";
                echo "\n";
                if ($r['status'] === 'ok') $count++;
            }
            if ($count === 0) echo "  Rien à migrer\n";
            echo "\n{$count} migration(s) exécutée(s)\n";
            break;

        case 'rollback':
            $steps = (int)($argv[2] ?? 1);
            echo "Rollback de {$steps} migration(s)...\n";
            $results = $migrator->rollback($steps);
            foreach ($results as $r) {
                echo "  [{$r['status']}] {$r['migration']}\n";
            }
            break;

        case 'status':
            echo "Statut des migrations:\n";
            $status = $migrator->status();
            foreach ($status as $s) {
                $icon = $s['executed'] ? "\033[32m✓\033[0m" : "\033[33m✗\033[0m";
                echo "  {$icon} {$s['migration']}\n";
            }
            break;

        default:
            echo "Usage: php database/migrate.php [migrate|rollback|status]\n";
    }
} catch (\Throwable $e) {
    echo "\033[31mERREUR: {$e->getMessage()}\033[0m\n";
    exit(1);
}

echo "\n";
