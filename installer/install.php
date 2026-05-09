#!/usr/bin/env php
<?php

echo "====================================================\n";
echo "  CUK-Admin - Installateur\n";
echo "  Centre Universitaire de Koulamoutou\n";
echo "====================================================\n\n";

$configFile = __DIR__ . '/../config/database.php';
$schemaFile = __DIR__ . '/schema.sql';
$seedFile = __DIR__ . '/seed.sql';

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
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Base de données '$dbname' créée\n";
    
    $pdo->exec("USE `$dbname`");
    
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
    $config = "<?php\n\nreturn [\n";
    $config .= "    'driver' => 'mysql',\n";
    $config .= "    'host' => '$host',\n";
    $config .= "    'database' => '$dbname',\n";
    $config .= "    'username' => '$user',\n";
    $config .= "    'password' => '$password',\n";
    $config .= "    'port' => $port,\n";
    $config .= "    'charset' => 'utf8mb4',\n";
    $config .= "    'collation' => 'utf8mb4_unicode_ci',\n";
    $config .= "    'prefix' => '',\n";
    $config .= "    'strict' => true,\n";
    $config .= "    'engine' => 'InnoDB',\n";
    $config .= "    'options' => [\n";
    $config .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $config .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $config .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
    $config .= "    ]\n";
    $config .= "];\n";
    
    file_put_contents($configFile, $config);
    echo "[OK] Configuration mise à jour\n";
    
    echo "\n====================================================\n";
    echo "  Installation terminée avec succès!\n";
    echo "====================================================\n\n";
    echo "Comptes par défaut:\n";
    echo "  - admin / password (root)\n";
    echo "  - secretaire / password (secrétaire)\n";
    echo "  - prof_ngouala / password (professeur)\n\n";
    echo "Pour lancer l'application:\n";
    echo "  php -S localhost:8000\n";
    echo "  puis ouvrir http://localhost:8000\n\n";
    
} catch (PDOException $e) {
    die("ERREUR: " . $e->getMessage() . "\n");
}