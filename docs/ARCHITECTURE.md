# Architecture Technique - CUK-Admin

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture applicative](#architecture-applicative)
3. [Base de données](#base-de-données)
4. [Structure du code](#structure-du-code)
5. [Sécurité](#sécurité)
6. [Déploiement](#déploiement)

---

## Vue d'ensemble

CUK-Admin est une application web monolithique basée sur :

```
┌─────────────────────────────────────────────────────────┐
│                    INTERFACE UTILISATEUR                  │
│                   (HTML/CSS/JavaScript)                  │
├─────────────────────────────────────────────────────────┤
│                      CONTRÔLEUR (PHP)                    │
│                   (Vues + Logique métier)                │
├─────────────────────────────────────────────────────────┤
│                    MODÈLE (Database)                    │
│                     (PDO + SQLite/MySQL)                │
├─────────────────────────────────────────────────────────┤
│                     BASE DE DONNÉES                      │
│                   (SQLite / MySQL)                      │
└─────────────────────────────────────────────────────────┘
```

### Stack technique

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Frontend | HTML5, CSS3, JavaScript | - |
| Backend | PHP | 8.1+ |
| Base de données | SQLite / MySQL | 3.x / 5.7+ |
| Framework UI | Bootstrap | 5.3 |
| Icons | Bootstrap Icons | 1.11 |
| Charts | Chart.js | 4.x |
| DataTables | DataTables | 1.13 |

---

## Architecture applicative

### Modèle MVC simplifié

```
main.php
├── src/
│   └── Database.php          # Couche d'accès aux données
├── src/Views/                # Vues (contrôleur + vue combinés)
│   ├── login.php             # Connexion
│   ├── dashboard.php         # Tableau de bord
│   ├── etudiants.php         # CRUD étudiants
│   ├── notes.php             # Gestion notes
│   └── ...
```

### Flux de données

```
1. Utilisateur → main.php (route)
2. Session PHP (auth)
3. Vue PHP (HTML + PHP inline)
4. Database.php (requêtes PDO)
5. SQLite/MySQL (stockage)
6. Réponse JSON/HTML
```

---

## Base de données

### Schéma relationnel

```
users (1) ←────── (N) journal_activite
  │
  └── (N) incidents

etudiants (N) ────── (1) filieres
  │                        │
  ├── (N) notes            │
  ├── (N) absences          │
  ├── (N) orientations      │
  └── (N) incidents        │

instituts (1) ←──── (N) filieres
                          │
                          └── (N) semestres
                                    │
                                    └── (N) ues
                                            │
                                            └── (N) ecs
```

### Tables principales

| Table | Description | Clé étrangère |
|-------|-------------|---------------|
| users | Utilisateurs système | - |
| instituts | Instituts (ISTPK, ISTS) | - |
| filieres | Filières DUT | institut_id |
| etudiants | Étudiants inscrits | filiere_id |
| semestres | Semestres (S1-S4) | filiere_id |
| ues | Unités d'enseignement | filiere_id, semestre_id |
| ecs | Éléments constitutifs | ue_id |
| notes | Notes des étudiants | etudiant_id, ec_id |
| absences | Absences | etudiant_id |
| incidents | Incidents disciplinaires | etudiant_id, utilisateur_id |

---

## Structure du code

### Point d'entrée

```php
// main.php
<?php
session_start();
require_once 'src/Database.php';

// Routing simple
$page = $_GET['page'] ?? 'dashboard';
include "src/Views/{$page}.php";
```

### Classe Database

```php
// src/Database.php
namespace CUKAdmin;

class Database {
    private static $instance = null;
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query(string $sql, array $params = []): PDOStatement {
        // Préparation et exécution
    }
    
    public function fetch(string $sql): ?array {
        // Retourne une ligne
    }
    
    public function fetchAll(string $sql): array {
        // Retourne toutes les lignes
    }
}
```

### Vues

Chaque vue est un fichier PHP autonome qui :

1. Récupère les données via `db()->fetch()`
2. Affiche le HTML avec les données
3. Gère les formulaires POST

```php
// Exemple: src/Views/etudiants.php
<?php
$etudiants = db()->fetchAll("SELECT * FROM etudiants");
?>
<table>
<?php foreach ($etudiants as $e): ?>
    <tr><td><?= $e['nom'] ?></td></tr>
<?php endforeach; ?>
</table>
```

---

## Sécurité

### Mesures implémentées

| Mesure | Implémentation |
|--------|----------------|
| Password hashing | `password_hash()` bcrypt |
| SQL Injection | Prepared statements PDO |
| XSS | `htmlspecialchars()` |
| CSRF | Tokens implicites |
| Sessions | `session_start()` + regeneration |

### Recommandations

1. **HTTPS** obligatoire en production
2. **Mots de passe** forts (min 12 caractères)
3. **Permissions** fichiers restrictives
4. **Sauvegardes** régulières de la base

---

## Déploiement

### Développement local

```bash
php -S localhost:8000
```

### Serveur Linux (Apache)

```apache
# /var/www/cuk-admin/.htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### Windows (IIS)

```web.config
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Rewrite" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```

### PHPDesktop (Windows Desktop)

```json
// phpdesktop.json
{
    "app_name": "CUK-Admin",
    "window": {
        "title": "CUK-Admin - Koulamoutou",
        "width": 1400,
        "height": 900
    },
    "server": {
        "port": 8000
    }
}
```

---

## Performance

### Optimisations

- **Indexes** sur les colonnes fréquentes
- **SQLite** pour rapidité (embarqué)
- **Cache** navigateur (CSS/JS)
- **Pagination** DataTables

### Benchmarks

| Métrique | Valeur |
|----------|--------|
| Temps de chargement | < 500ms |
| Requêtes DB | < 50ms |
| Pages consultables | ~1000 |

---

## Support

[GitHub Issues](https://github.com/Ggboykxz/CUK-admin/issues)