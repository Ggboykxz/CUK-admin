# CUK-Admin

## Centre Universitaire de Koulamoutou - Système de Gestion Universitaire

Application de gestion desktop pour le Centre Universitaire de Koulamoutou (CUK), province de l'Ogooué-Lolo, Gabon.

### Filières DUT

Le CUK propose **7 filières en cycle DUT** (Diplôme Universitaire de Technologie) réparties dans **2 instituts** :

#### Institut des Sciences et Technique Paul Kouya (ISTPK)
1. DUT en Architecture et Éco-construction
2. DUT en Chimie Industrielle
3. DUT en Génie Thermique et Énergies Renouvelables
4. DUT en Informatique et Communication
5. DUT en Productique Mécanique

#### Institut des Sciences Technologiques de la Santé (ISTS)
6. DUT en Analyses Biologiques et Biochimiques
7. DUT en Maintenance des Équipements Biomédicaux

### Fonctionnalités

- **Module Étudiants**: Inscription, gestion des parcours, documents
- **Module Notes**: Saisie des notes par EC, calcul automatique des moyennes
- **Module Absences**: Suivi des présences et absences
- **Module Filières**: Gestion des Instituts, UE et EC
- **Module Disciplinarité**: Signalement et suivi des incidents
- **Module Orientations**: Transferts et réorientations
- **Module Rapports**: Statistiques et graphiques
- **Module Utilisateurs**: Gestion des comptes et rôles
- **Module Paramètres**: Configuration du système

### Prérequis

- PHP 8.x ou supérieur
- MySQL/MariaDB
- Serveur web (Apache, Nginx) ou PHP Desktop

### Installation

1. Créer la base de données:
```sql
CREATE DATABASE cuk_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importer le schéma:
```bash
mysql -u root -p cuk_admin < database/schema.sql
mysql -u root -p cuk_admin < database/seed.sql
```

3. Configurer la connexion dans `config/database.php`

4. Lancer le serveur:
```bash
php -S localhost:8000
```

5. Accéder à http://localhost:8000

### Comptes par défaut

| Utilisateur | Mot de passe | Rôle |
|-------------|--------------|------|
| admin | password | root |
| secretaire | password | Secrétaire |
| prof_ngouala | password | Professeur |

### Structure

```
CUK-Admin/
├── main.php              # Point d'entrée
├── index.php             # Alias
├── config/
│   └── database.php      # Configuration BDD
├── database/
│   ├── schema.sql        # Schéma complet (avec Instituts)
│   └── seed.sql          # Données initiales (7 filières DUT)
├── src/
│   ├── Database.php      # Classe de connexion
│   └── Views/           # Vues PHP (10 modules)
├── assets/
│   ├── css/style.css     # Styles
│   └── js/app.js         # JavaScript
└── installer/            # Installateur
```

### Calcul des moyennes DUT

- **Moyenne EC** = (CC × coef_cc + TP × coef_tp + Examen × coef_examen) / somme(coefs)
- **Moyenne UE** = Σ(Moy_EC × crédits_EC) / Σ(crédits_EC)
- **Moyenne Semestre** = Σ(Moy_UE × crédits_UE) / Σ(crédits_UE)
- **Crédits DUT** = 120 (4 semestres × 30 crédits)

### Mentions

| Moyenne | Mention |
|---------|---------|
| ≥ 18 | Excellent |
| ≥ 16 | Très Bien |
| ≥ 14 | Bien |
| ≥ 12 | Assez Bien |
| ≥ 10 | Passable |
| < 10 | Ajourné |

### Licence

© 2025-2026 Centre Universitaire de Koulamoutou