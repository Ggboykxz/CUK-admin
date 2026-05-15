#!/usr/bin/env php
<?php

declare(strict_types=1);

echo "====================================================\n";
echo "  CUK-Admin - Installateur\n";
echo "  Centre Universitaire de Koulamoutou\n";
echo "====================================================\n\n";

$configFile = __DIR__ . '/../config/database.php';
$schemaFile = __DIR__ . '/../database/schema.sql';
$seedFile = __DIR__ . '/../database/seed.sql';

echo "Vérification des fichiers...\n";

if (!file_exists($configFile)) {
    die("ERREUR: Fichier de configuration introuvable.\n");
}

if (!file_exists($schemaFile)) {
    die("ERREUR: Schéma de base de données introuvable.\n");
}

echo "[OK] Fichiers détectés\n\n";

echo "Configuration de la base de données:\n";
echo "-----------------------------------\n";

echo "Hôte MySQL [localhost]: ";
$host = trim(fgets(STDIN)) ?: 'localhost';

echo "Port [3306]: ";
$port = trim(fgets(STDIN)) ?: '3306';

echo "Utilisateur MySQL [root]: ";
$user = trim(fgets(STDIN)) ?: 'root';

echo "Mot de passe MySQL: ";
$password = trim(fgets(STDIN));

echo "Nom de la base de données [cuk_admin]: ";
$dbname = trim(fgets(STDIN)) ?: 'cuk_admin';

echo "\nCréation de la base de données...\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%d', $host, (int)$port);
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $safeDbname = preg_replace('/[^a-zA-Z0-9_]/', '', $dbname);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Base de données '{$safeDbname}' créée\n";

    $pdo->exec("USE `{$safeDbname}`");

    echo "\nImportation du schéma...\n";
    $schema = file_get_contents($schemaFile);
    $pdo->exec($schema);
    echo "[OK] Schéma importé\n";

    if (file_exists($seedFile)) {
        echo "\nImportation des données initiales...\n";
        $seed = file_get_contents($seedFile);
        $pdo->exec($seed);
        echo "[OK] Données initiales importées\n";
    }

    echo "\nMise à jour de la configuration...\n";
    $safeUser = preg_replace('/[^a-zA-Z0-9_]/', '', $user);
    $safePassword = str_replace(["'", '\\'], ["\\'", '\\\\'], $password);

    $config = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n";
    $config .= "    'driver' => 'mysql',\n";
    $config .= "    'host' => '{$host}',\n";
    $config .= "    'database' => '{$safeDbname}',\n";
    $config .= "    'username' => '{$safeUser}',\n";
    $config .= "    'password' => '{$safePassword}',\n";
    $config .= "    'port' => " . (int)$port . ",\n";
    $config .= "    'charset' => 'utf8mb4',\n";
    $config .= "    'collation' => 'utf8mb4_unicode_ci',\n";
    $config .= "    'prefix' => '',\n";
    $config .= "    'strict' => true,\n";
    $config .= "    'engine' => 'InnoDB',\n";
    $config .= "    'options' => [\n";
    $config .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $config .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $config .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
    $config .= "    ],\n";
    $config .= "];\n";

    file_put_contents($configFile, $config);
    echo "[OK] Configuration mise à jour\n";

    echo "\n====================================================\n";
    echo "  Installation terminée avec succès!\n";
    echo "====================================================\n\n";
    echo "Comptes par défaut (développement uniquement):\n";
    echo "  - admin / CUK2025_Admin! (root)\n";
    echo "  - secretaire / CUK2025_Secretaire! (secrétaire)\n";
    echo "  - prof_ngouala / CUK2025_Prof1! (professeur)\n";
    echo "  - prof_mouyama / CUK2025_Prof2! (professeur)\n\n";
    echo "IMPORTANT: Le système forcera le changement de mot de passe après la première connexion.\n\n";
    echo "Pour lancer l'application:\n";
    echo "  php -S localhost:8000\n";
    echo "  puis ouvrir http://localhost:8000\n\n";

} catch (PDOException $e) {
    die("ERREUR: " . $e->getMessage() . "\n");
}
