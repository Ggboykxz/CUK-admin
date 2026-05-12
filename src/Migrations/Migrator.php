<?php

declare(strict_types=1);

namespace CUK\Migrations;

class Migrator
{
    private \PDO $pdo;
    private string $table = 'migrations';
    private string $path;

    public function __construct(\PDO $pdo, ?string $path = null)
    {
        $this->pdo = $pdo;
        $this->path = $path ?? __DIR__ . '/../../database/migrations/';
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function getExecuted(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM {$this->table} ORDER BY id");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function migrate(): array
    {
        $executed = $this->getExecuted();
        $files = glob($this->path . '*.sql');
        sort($files);

        $results = [];

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $executed, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (migration) VALUES (:migration)");
                $stmt->execute(['migration' => $name]);
                $results[] = ['migration' => $name, 'status' => 'ok'];
            } catch (\PDOException $e) {
                $results[] = ['migration' => $name, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function rollback(int $steps = 1): array
    {
        $executed = $this->getExecuted();
        $toRollback = array_slice(array_reverse($executed), 0, $steps);
        $results = [];

        foreach ($toRollback as $name) {
            $rollbackFile = str_replace('.sql', '.rollback.sql', $this->path . $name);
            if (!file_exists($rollbackFile)) {
                $results[] = ['migration' => $name, 'status' => 'skipped', 'error' => 'No rollback file'];
                continue;
            }

            $sql = file_get_contents($rollbackFile);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE migration = :migration");
                $stmt->execute(['migration' => $name]);
                $results[] = ['migration' => $name, 'status' => 'rolled_back'];
            } catch (\PDOException $e) {
                $results[] = ['migration' => $name, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function status(): array
    {
        $executed = $this->getExecuted();
        $files = glob($this->path . '*.sql');
        sort($files);

        $status = [];
        foreach ($files as $file) {
            $name = basename($file);
            $status[] = [
                'migration' => $name,
                'executed' => in_array($name, $executed, true),
            ];
        }

        return $status;
    }
}
