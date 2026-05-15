<?php

$mysql = file_get_contents(__DIR__ . '/schema.sql');

$sqlite = $mysql;
$sqlite = preg_replace('/INT\s+UNSIGNED\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlite);
$sqlite = preg_replace('/INT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlite);
$sqlite = preg_replace('/ENGINE\s*=\s*InnoDB/i', '', $sqlite);
$sqlite = preg_replace('/DEFAULT\s+CHARSET\s*=\s*utf8mb4/i', '', $sqlite);
$sqlite = preg_replace('/COLLATE\s*=\s*utf8mb4_unicode_ci/i', '', $sqlite);
$sqlite = preg_replace('/CHARACTER\s+SET\s*utf8mb4/i', '', $sqlite);
$sqlite = preg_replace('/TINYINT\(1\)/i', 'INTEGER', $sqlite);
$sqlite = preg_replace('/INT\(\d+\)/i', 'INTEGER', $sqlite);
$sqlite = preg_replace('/BIGINT\(\d+\)/i', 'INTEGER', $sqlite);
$sqlite = preg_replace('/DECIMAL\(\d+,\d+\)/i', 'REAL', $sqlite);
$sqlite = preg_replace('/DOUBLE/i', 'REAL', $sqlite);
$sqlite = preg_replace('/ENUM\([^)]+\)/i', 'VARCHAR(50)', $sqlite);
$sqlite = preg_replace('/NOW\(\)/i', 'CURRENT_TIMESTAMP', $sqlite);
$sqlite = preg_replace('/CURRENT_TIMESTAMP\(\)/i', 'CURRENT_TIMESTAMP', $sqlite);
$sqlite = preg_replace('/`/m', '"', $sqlite);
$sqlite = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $sqlite);
$sqlite = preg_replace('/SET\s+NAMES\s+utf8mb4/i', '', $sqlite);
$sqlite = preg_replace('/SET\s+FOREIGN_KEY_CHECKS\s*=\s*\d/i', '', $sqlite);

// Remove KEY definitions (already handled by PRIMARY KEY and FOREIGN KEY)
$sqlite = preg_replace('/,\s*\n\s*KEY\s+"[^"]+"\s*\([^)]+\)/i', '', $sqlite);
$sqlite = preg_replace('/KEY\s+"[^"]+"\s*\([^)]+\),\s*/i', '', $sqlite);

// Remove UNIQUE KEY definitions (constraint is on columns directly)
$sqlite = preg_replace('/,\s*\n\s*UNIQUE\s+KEY\s+"[^"]+"\s*\([^)]+\)/i', '', $sqlite);
$sqlite = preg_replace('/UNIQUE\s+KEY\s+"[^"]+"\s*\([^)]+\),\s*/i', '', $sqlite);

// Fix trailing commas before closing paren
$lines = explode("\n", $sqlite);
$result = [];
$prevWasComma = false;
foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    if ($trimmed === ')' || str_starts_with($trimmed, ');')) {
        // Remove trailing comma from previous line
        if (!empty($result)) {
            $last = array_pop($result);
            $last = rtrim($last);
            $last = preg_replace('/,$/', '', $last);
            $result[] = $last;
        }
    }
    $result[] = $line;
}
$sqlite = implode("\n", $result);

file_put_contents(__DIR__ . '/schema_sqlite.sql', $sqlite);
echo "Generated schema_sqlite.sql\n";

preg_match_all('/CREATE\s+TABLE/i', $sqlite, $matches);
echo 'Tables: ' . count($matches[0]) . "\n";
