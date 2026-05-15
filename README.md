# CUK-Admin 🏛️

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/Ggboykxz/CUK-admin)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Database](https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL-orange.svg)]()

> **Système de Gestion Universitaire** pour le Centre Universitaire de Koulamoutou (CUK), province de l'Ogooué-Lolo, Gabon.

## 📋 Table des matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Structure du projet](#-structure-du-projet)
- [Filières DUT](#-filières-dut)
- [Documentation](#-documentation)
- [Contribution](#-contribution)
- [Licence](#-licence)

---

## 🏫 À propos

Le **CUK-Admin** est une application desktop de gestion universitaire permettant d'administrer l'ensemble du cycle DUT (Diplôme Universitaire de Technologie) du Centre Universitaire de Koulamoutou.

### Caractéristiques principales

- ✅ Application desktop Windows (PHPDesktop)
- ✅ Base de données portable (SQLite) ou serveur (MySQL)
- ✅ Interface responsive en français
- ✅ Calcul automatique des moyennes DUT
- ✅ Gestion complète des étudiants et notes

---

## ✨ Fonctionnalités

| Module | Description |
|--------|-------------|
| **Étudiants** | Inscription, parcours, documents, statuts |
| **Notes** | Saisie CC/TP/Examen, calcul des moyennes |
| **Absences** | Suivi des présences et absences |
| **Filières** | Gestion des Instituts, UE et EC |
| **Disciplinarité** | Signalement et suivi des incidents |
| **Orientations** | Transferts et réorientations |
| **Rapports** | Statistiques et graphiques |
| **Utilisateurs** | Gestion des rôles (root, admin, secretaire, prof) |
| **Paramètres** | Configuration du système |

---

## 🚀 Installation

### Prérequis

- **PHP** 8.1 ou supérieur
- **SQLite** ou **MySQL/MariaDB**
- **Composer** (pour le développement)

### Installation rapide (SQLite)

```bash
# Cloner le projet
git clone https://github.com/Ggboykxz/CUK-admin.git
cd CUK-admin

# Lancer directement avec PHP内置服务器
php -S localhost:8000

# Ouvrir http://localhost:8000
```

### Installation avec MySQL

```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE cuk_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# Importer le schéma
mysql -u root -p cuk_admin < database/schema.sql
mysql -u root -p cuk_admin < database/seed.sql

# Modifier la configuration
# Éditer config/database.php avec vos credentials
```

### Installation pour Windows Desktop

1. Télécharger [PHPDesktop](https://github.com/cztomczak/phpdesktop)
2. Copier les fichiers du projet dans le dossier PHPDesktop
3. Configurer `phpdesktop.json`
4. Lancer `phpdesktop.exe`

---

## ⚙️ Configuration

### Base de données SQLite (par défaut)

```php
// config/database.php
return [
    'driver' => 'sqlite',
    'database' => 'database/cuk_admin.sqlite',
];
```

### Base de données MySQL

```php
// config/database.php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'cuk_admin',
    'username' => 'root',
    'password' => 'votre_mot_de_passe',
    'port' => 3306,
];
```

### Comptes par défaut (développement uniquement)

| Utilisateur | Mot de passe | Rôle | Description |
|-------------|--------------|------|-------------|
| `admin` | `CUK2025_Admin!` | root | Administrateur système |
| `secretaire` | `CUK2025_Secretaire!` | secretaire | Gestionnaire inscriptions |
| `prof_ngouala` | `CUK2025_Prof1!` | professeur | Enseignant |
| `prof_mouyama` | `CUK2025_Prof2!` | professeur | Enseignant |

> ⚠️ **Important**: Changez les mots de passe après la première connexion ! Le système forcera le changement pour tout compte utilisant encore un mot de passe par défaut.

---

## 📁 Structure du projet

```
CUK-Admin/
├── main.php                 # Point d'entrée principal
├── index.php                # Alias de main.php
├── AGENTS.md                 # Documentation pour développement IA
├── README.md                 # Ce fichier
├── LICENSE                   # Licence MIT
├── CHANGELOG.md              # Historique des versions
├── .gitignore               # Fichiers à ignorer
├── .editorconfig             # Configuration éditeur
├── composer.json             # Dépendances PHP
├── config/
│   └── database.php          # Configuration base de données
├── database/
│   ├── schema.sql            # Schéma MySQL
│   ├── schema_sqlite.sql     # Schéma SQLite
│   ├── seed.sql             # Données MySQL
│   ├── seed_sqlite.sql       # Données SQLite
│   └── cuk_admin.sqlite      # Base SQLite pré-configurée
├── src/
│   ├── Database.php          # Classe de connexion
│   └── Views/                # Vues PHP
│       ├── dashboard.php     # Tableau de bord
│       ├── etudiants.php      # Gestion étudiants
│       ├── notes.php          # Saisie des notes
│       ├── absences.php       # Suivi absences
│       ├── filieres.php      # Gestion filières
│       ├── disciplinarite.php # Incidents
│       ├── orientations.php   # Transferts
│       ├── rapports.php       # Statistiques
│       ├── utilisateurs.php   # Gestion users
│       ├── parametres.php     # Configuration
│       └── login.php          # Page connexion
├── assets/
│   ├── css/
│   │   └── style.css         # Styles principaux
│   └── js/
│       └── app.js           # JavaScript
├── installer/
│   ├── install.php          # Script installation
│   └── phpdesktop.json      # Config PHPDesktop
├── reports/                  # Rapports générés
└── docs/                     # Documentation additionnelle
    ├── INSTALLATION.md       # Guide d'installation détaillé
    ├── UTILISATION.md        # Guide utilisateur
    └── ARCHITECTURE.md        # Documentation technique
```

---

## 🎓 Filières DUT

Le CUK propose **7 filières en cycle DUT** (120 crédits, 2 ans) :

### Institut des Sciences et Technique Paul Kouya (ISTPK)

| Code | Filière DUT | Crédits |
|------|-------------|---------|
| ISTPK-AEC | Architecture et Éco-construction | 120 |
| ISTPK-CI | Chimie Industrielle | 120 |
| ISTPK-GTER | Génie Thermique et Énergies Renouvelables | 120 |
| ISTPK-IC | Informatique et Communication | 120 |
| ISTPK-PM | Productique Mécanique | 120 |

### Institut des Sciences Technologiques de la Santé (ISTS)

| Code | Filière DUT | Crédits |
|------|-------------|---------|
| ISTS-ABB | Analyses Biologiques et Biochimiques | 120 |
| ISTS-MEB | Maintenance des Équipements Biomédicaux | 120 |

---

## 📊 Calcul des moyennes DUT

### Formules

```
Moyenne EC = (CC × coef_cc + TP × coef_tp + Examen × coef_examen) / somme(coefs)
Moyenne UE = Σ(Moy_EC × crédits_EC) / Σ(crédits_EC)
Moyenne Semestre = Σ(Moy_UE × crédits_UE) / Σ(crédits_UE)
Moyenne DUT = moyenne des 4 semestres (180 crédits)
```

### Mentions

| Moyenne | Mention |
|---------|---------|
| ≥ 18 | Excellent |
| ≥ 16 | Très Bien |
| ≥ 14 | Bien |
| ≥ 12 | Assez Bien |
| ≥ 10 | Passable |
| < 10 | Ajourné |

### Règles de validation

- ✅ Validation UE: moyenne ≥ 10
- ✅ Compensation: moyenne générale ≥ 10
- 🔄 Redoublement: moyenne < 10 sans compensation
- 📈 Passage S2→S3: 60 crédits minimum
- 🎓 Obtention DUT: 120 crédits validés

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| [AGENTS.md](AGENTS.md) | Instructions pour développement IA avec OpenCode |
| [INSTALLATION.md](docs/INSTALLATION.md) | Guide d'installation détaillé |
| [UTILISATION.md](docs/UTILISATION.md) | Guide utilisateur |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Documentation technique |
| [CHANGELOG.md](CHANGELOG.md) | Historique des modifications |

---

## 🤝 Contribution

Les contributions sont les bienvenues! 

1. Fork le projet
2. Créez une branche (`git checkout -b feature/Amelioration`)
3. Commit (`git commit -m 'feat: Amélioration description'`)
4. Push (`git push origin feature/Amelioration`)
5. Ouvrez une Pull Request

Voir [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails.

---

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👥 Équipe

- **Développement**: Équipe CUK
- **Institution**: Centre Universitaire de Koulamoutou
- **Localisation**: Koulamoutou, Province de l'Ogooué-Lolo, Gabon

---

## 📞 Support

- 📧 Email: contact@cuk-gabon.ga
- 🌐 Web: https://cuk-gabon.ga
- 📝 Issues: [GitHub Issues](https://github.com/Ggboykxz/CUK-admin/issues)

---

<div align="center">
  <p>© 2025-2026 Centre Universitaire de Koulamoutou</p>
  <p>Construit avec ❤️ pour l'éducation au Gabon 🇬🇦</p>
</div>