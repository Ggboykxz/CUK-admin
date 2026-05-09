<?php

namespace CUKAdmin;

class Database
{
    private static ?Database $instance = null;
    private $connection;
    private $driver;

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
                $config['options']
            );
        } else {
            $dbPath = __DIR__ . '/../' . ($config['database'] ?? 'database/cuk_admin.sqlite');
            $this->connection = new \PDO('sqlite:' . $dbPath);
            $this->connection->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
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
        $columns = implode(', ', array_keys($data));
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
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $setString = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";
        $stmt = $this->query($sql, array_merge($data, $whereParams));

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
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
        $sql = preg_replace(
            '/COLLATE\s*=\s*utf8mb4_unicode_ci/i',
            '',
            $sql
        );
        $sql = preg_replace('/CHARACTER\s+SET\s*utf8mb4/i', '', $sql);
        $sql = preg_replace('/TINYINT\(1\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/INT\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/BIGINT\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/DECIMAL\(\d+,\d+\)/i', 'REAL', $sql);
        $sql = preg_replace('/DOUBLE/i', 'REAL', $sql);
        $sql = preg_replace(
            '/ENUM\([^)]+\)/i',
            'VARCHAR(50)',
            $sql
        );
        $sql = preg_replace(
            '/SET\s+NULL/i',
            'NULL',
            $sql
        );
        $sql = preg_replace(
            '/SET\s+RESTRICT/i',
            'RESTRICT',
            $sql
        );
        $sql = preg_replace(
            '/ON\s+DELETE\s+CASCADE/i',
            '',
            $sql
        );
        $sql = preg_replace(
            '/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i',
            '',
            $sql
        );
        $sql = preg_replace(
            '/ON\s+DELETE\s+RESTRICT/i',
            '',
            $sql
        );
        $sql = preg_replace('/NOW\(\)/i', 'CURRENT_TIMESTAMP', $sql);
        $sql = preg_replace(
            '/CURRENT_TIMESTAMP\(\)/i',
            'CURRENT_TIMESTAMP',
            $sql
        );

        return trim($sql);
    }
}

function db(): Database
{
    return Database::getInstance();
}
