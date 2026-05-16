<?php

declare(strict_types=1);

namespace CUK;

class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;
    private string $driver;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $this->driver = $config['driver'] ?? 'sqlite';

        if ($this->driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            $this->connection = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
        } else {
            $dbPath = $config['database'] ?? 'database/cuk_admin.sqlite';
            $resolved = realpath(__DIR__ . '/../' . $dbPath);
            if ($resolved === false || strpos($resolved, realpath(__DIR__ . '/../database')) !== 0) {
                $resolved = __DIR__ . '/../database/cuk_admin.sqlite';
            }
            $this->connection = new \PDO('sqlite:' . $resolved);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->autoInitialize();
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        if ($this->driver === 'sqlite') {
            $sql = $this->convertMysqlToSqlite($sql);
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(string $table, array $data): int
    {
        $this->validateTableName($table);
        $safeKeys = [];
        foreach (array_keys($data) as $col) {
            $safeKeys[] = $this->quoteIdentifier($col);
        }
        $columns = implode(', ', $safeKeys);
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return (int) $this->connection->lastInsertId();
    }

    public function update(
        string $table,
        array $data,
        string $where,
        array $whereParams = []
    ): int {
        $this->validateTableName($table);
        $set = [];
        foreach (array_keys($data) as $column) {
            $safeCol = $this->quoteIdentifier($column);
            $set[] = "{$safeCol} = :{$column}";
        }
        $setString = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
        $stmt = $this->query($sql, array_merge($data, $whereParams));

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $this->validateTableName($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    private function validateTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException("Nom de table invalide: {$table}");
        }
    }

    private function quoteIdentifier(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException("Nom de colonne invalide: {$name}");
        }
        return $this->driver === 'mysql' ? "`{$name}`" : "\"{$name}\"";
    }

    private function autoInitialize(): void
    {
        try {
            $count = $this->connection->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
            if ((int)$count === 0) {
                $schemaFile = __DIR__ . '/../database/schema_sqlite.sql';
                $seedFile = __DIR__ . '/../database/seed_sqlite.sql';
                if (file_exists($schemaFile)) {
                    $this->connection->exec('PRAGMA foreign_keys = OFF');
                    $this->connection->exec(file_get_contents($schemaFile));
                    if (file_exists($seedFile)) {
                        $this->connection->exec(file_get_contents($seedFile));
                    }
                    $this->connection->exec('PRAGMA foreign_keys = ON');
                }
            }
        } catch (\Throwable $e) {
            // Silently fail - will be caught by the caller
        }
    }

    private function convertMysqlToSqlite(string $sql): string
    {
        $sql = preg_replace(
            '/INT\s+UNSIGNED\s+AUTO_INCREMENT/i',
            'INTEGER PRIMARY KEY AUTOINCREMENT',
            $sql
        );
        $sql = preg_replace(
            '/INT\s+AUTO_INCREMENT/i',
            'INTEGER PRIMARY KEY AUTOINCREMENT',
            $sql
        );
        $sql = preg_replace('/ENGINE\s*=\s*InnoDB/i', '', $sql);
        $sql = preg_replace('/DEFAULT\s+CHARSET\s*=\s*utf8mb4/i', '', $sql);
        $sql = preg_replace('/COLLATE\s*=\s*utf8mb4_unicode_ci/i', '', $sql);
        $sql = preg_replace('/CHARACTER\s+SET\s*utf8mb4/i', '', $sql);
        $sql = preg_replace('/TINYINT\(1\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/INT\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/BIGINT\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/DECIMAL\(\d+,\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/DOUBLE/i', 'REAL', $sql);
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sql);
        $sql = preg_replace('/NOW\(\)/i', 'CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace('/CURRENT_TIMESTAMP\(\)/i', 'CURRENT_TIMESTAMP', $sql);

        return trim($sql);
    }
}


