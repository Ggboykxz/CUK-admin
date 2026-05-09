# Guide d'Installation - CUK-Admin

## Table des matières

1. [Prérequis](#prérequis)
2. [Installation rapide](#installation-rapide)
3. [Installation SQLite](#installation-sqlite)
4. [Installation MySQL](#installation-mysql)
5. [Installation Windows Desktop](#installation-windows-desktop)
6. [Configuration](#configuration)
7. [Dépannage](#dépannage)

---

## Prérequis

### Logiciels nécessaires

| Logiciel | Version | Lien |
|----------|---------|------|
| PHP | 8.1+ | [php.net](https://www.php.net/downloads.php) |
| SQLite | 3.x | Inclus avec PHP |
| MySQL (optionnel) | 5.7+ | [mysql.com](https://dev.mysql.com/downloads/mysql/) |
| Composer (optionnel) | Latest | [getcomposer.org](https://getcomposer.org/download/) |

### Extensions PHP requises

- `pdo_sqlite` ou `pdo_mysql`
- `mbstring`
- `json`
- `session`

Pour vérifier :
```bash
php -m | grep -E "pdo|mbstring"
```

---

## Installation rapide

### 1. Cloner le projet

```bash
git clone https://github.com/Ggboykxz/CUK-admin.git
cd CUK-admin
```

### 2. Lancer le serveur

```bash
php -S localhost:8000
```

### 3. Accéder à l'application

Ouvrez votre navigateur : **http://localhost:8000**

### 4. Connexion

```
Utilisateur: admin
Mot de passe: password
```

---

## Installation SQLite

L'installation SQLite est recommandée pour les tests et le déploiement simple.

### Étapes

1. **La base SQLite est déjà incluse** dans `database/cuk_admin.sqlite`

2. **Vérifier les permissions** :
```bash
chmod 755 database/
chmod 644 database/cuk_admin.sqlite
```

3. **Lancer l'application** :
```bash
php -S localhost:8000
```

### Créer une nouvelle base SQLite

```bash
# Créer la base
sqlite3 database/cuk_admin.sqlite < database/schema_sqlite.sql

# Importer les données
sqlite3 database/cuk_admin.sqlite < database/seed_sqlite.sql

# Vérifier
sqlite3 database/cuk_admin.sqlite "SELECT COUNT(*) FROM etudiants;"
```

---

## Installation MySQL

### 1. Créer la base de données

```bash
mysql -u root -p -e "CREATE DATABASE cuk_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Importer le schéma

```bash
mysql -u root -p cuk_admin < database/schema.sql
```

### 3. Importer les données

```bash
mysql -u root -p cuk_admin < database/seed.sql
```

### 4. Configurer la connexion

Éditer `config/database.php` :

```php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'cuk_admin',
    'username' => 'root',
    'password' => 'votre_mot_de_passe',
    'port' => 3306,
];
```

### 5. Vérifier l'installation

```bash
mysql -u root -p cuk_admin -e "SELECT COUNT(*) FROM etudiants;"
```

---

## Installation Windows Desktop

### Option 1 : PHPDesktop

1. Télécharger [PHPDesktop](https://github.com/cztomczak/phpdesktop)
2. Extraire dans `C:\Program Files\CUK-Admin\`
3. Copier les fichiers du projet dans `www/`
4. Configurer `phpdesktop.json` :
```json
{
    "chrome": {
        "port": 20105
    },
    "server": {
        "port": 8000
    }
}
```
5. Lancer `CUK-Admin.exe`

### Option 2 : XAMPP/WAMP

1. Installer XAMPP ou WAMP
2. Copier les fichiers dans `C:\xampp\htdocs\CUK-Admin\`
3. Importer la base de données
4. Accéder via `http://localhost/CUK-Admin`

---

## Configuration

### Structure de configuration

```php
// config/database.php
return [
    // Driver: 'sqlite' ou 'mysql'
    'driver' => 'sqlite',
    
    // Pour SQLite
    'database' => 'database/cuk_admin.sqlite',
    
    // Pour MySQL (si driver = 'mysql')
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'port' => 3306,
];
```

### Variables d'environnement (optionnel)

Créer un fichier `.env` :

```env
DB_DRIVER=sqlite
DB_PATH=database/cuk_admin.sqlite
DB_HOST=localhost
DB_NAME=cuk_admin
DB_USER=root
DB_PASS=
```

### Configuration du serveur

Pour Apache (`.htaccess`) :
```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

Pour Nginx :
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Dépannage

### Erreur "Cannot find database"

```
Solution: Vérifier le chemin dans config/database.php
```

### Erreur "Permission denied"

```bash
# Linux/Mac
chmod 755 database/
chmod 644 database/*.sqlite

# Windows: clic droit > Propriétés > Sécurité
```

### Erreur "SQLSTATE[HY000]"

```
Solution: Vérifier les credentials MySQL
```

### Page blanche

```bash
# Vérifier les erreurs PHP
php -l main.php
php -l src/Database.php

# Activer l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Problème de session

```bash
# Créer le dossier sessions
mkdir -p tmp/sessions
chmod 777 tmp/sessions
```

---

## Installation en production

### Checklist avant déploiement

- [ ] Modifier les mots de passe par défaut
- [ ] Activer HTTPS
- [ ] Configurer le pare-feu
- [ ] Créer un utilisateur MySQL limité
- [ ] Sauvegarder la base régulièrement

### Recommandations de sécurité

1. **Changer le mot de passe admin** immédiatement
2. **Utiliser HTTPS** en production
3. **Limiter l'accès** à l'IP du campus
4. **Sauvegarder** la base chaque jour

---

## Support

Pour toute question : [GitHub Issues](https://github.com/Ggboykxz/CUK-admin/issues)