# AGENTS.md - CUK-Admin: Système de Gestion Universitaire

## Contexte du Projet

**CUK - Centre Universitaire de Koulamoutou** est une institution d'enseignement supérieur située à Koulamoutou, capitale de la province de l'Ogooué-Lolo au Gabon. Héritier des structures de l'ancien Lycée polytechnique Paul Kouya, cet établissement incarne la volonté politique de décentralisation de l'offre éducative au Gabon.

**Ouverture**: Année académique 2025-2026

**Offre de formation**: Le CUK propose des formations en **cycle DUT** (Diplôme Universitaire de Technologie) dans deux instituts :

### Institut des Sciences et Technique Paul Kouya (ISTPK)
- DUT en Architecture et Éco-construction
- DUT en Chimie Industrielle
- DUT en Génie Thermique et Énergies Renouvelables
- DUT en Informatique et Communication
- DUT en Productique Mécanique

### Institut des Sciences Technologiques de la Santé (ISTS)
- DUT en Analyses Biologiques et Biochimiques
- DUT en Maintenance des Équipements Biomédicaux

## Stack Technologique

- **Langage**: PHP 8.x avec **PHP-GTK** ou **PHPDesktop** (Chrome)
- **Base de données**: MySQL/MariaDB avec **SQLite** comme option portable
- **Interface**: HTML/CSS/JavaScript (embedded via PHP)
- **Architecture**: Application desktop Windows autonome

## Structure du Projet

```
CUK-Admin/
├── main.php                 # Point d'entrée principal
├── config/
│   └── database.php         # Configuration base de données
├── database/
│   ├── schema.sql           # Schéma complet de la BDD
│   └── seed.sql             # Données initiales
├── src/
│   ├── Models/              # Classes modèle
│   ├── Controllers/         # Logique métier
│   └── Views/               # Vues HTML/CSS/JS
├── assets/
│   ├── css/
│   ├── js/
│   └── icons/
├── reports/                  # Génération de rapports PDF
└── installer/               # Script d'installation
```

## Modules à Implémenter

### 1. Module Authentification
- Connexion sécurisée avec rôle (Administrateur,root)
- Gestion des sessions
- Journal d'activité

### 2. Module Étudiants
- **Inscription**: Numéro étudiant auto, photo, état civil, coordonnées
- **Parcours**: Institut, filière DUT, semestre (S1-S4)
- **Documents**: CNI, baccalauréat, diplômes uploadés
- **Statut**: Actif, suspendu, diplômé, abandonné

### 3. Module Académique DUT
- **Structure DUT**: 4 semestres (S1, S2, S3, S4)
- **Notes**: Saisie par matière/étudiant
- **Moyennes**: Calcul automatique par UE, EC, semestre
- **Moyennes pondérées**: Crédits pris en compte
- **Relevés**: Génération de relevés PDF
- **Jury**: Validation des résultats

### 4. Module Gestion des Notes DUT

#### Structure des UE et EC:
```
Institut (ISTPK / ISTS)
└── Filière DUT
    └── Semestre (S1, S2, S3, S4)
        └── UE (Unité d'Enseignement)
            └── EC (Élément Constitutif)
                └── Notes (CC, TP, Examen)
```

#### Calculs:
- Moyenne EC = (CC × coef1 + TP × coef2 + Examen × coef3) / somme(coefs)
- Moyenne UE = Σ(Moy_EC × crédits_EC) / Σ(crédits_EC)
- Moyenne Semestre = Σ(Moy_UE × crédits_UE) / Σ(crédits_UE)
- Moyenne Annuelle = (S1 + S2) / 2
- Moyenne DUT = moyenne des 4 semestres

#### Règles:
- Validation UE: moyenne UE ≥ 10
- Compensation: si moyenne générale ≥ 10
- Redoublement: si moyenne < 10 sans compensation
- Passage S2→S3: validation du S1 et S2 (60 crédits minimum)
- Obtention DUT: validation des 4 semestres (120 crédits minimum)
- Mention: Passable (10-12), Assez Bien (12-14), Bien (14-16), Très Bien (16-18), Excellent (>18)

### 5. Module Filières et Matières
- Gestion des deux institutes (ISTPK, ISTS)
- Filières DUT par institut
- Matières par semestre avec coefficients
- Enseignants assignés

### 6. Module Disciplinarité
- Incidents avec types (retard, absence, fraude, etc.)
- Gravité (mineur, majeur, grave)
- Mesures disciplinaires
- Historique complet

### 7. Module Orientation et Affectation
- Transferts inter-filières
- Mentions et classements
- Passage DUT vers Licence (L3)

### 8. Module Absences et Présences
- Appel par classe/semestre
- Taux de présence
- Alertes pour seuils critiques

### 9. Module Rapports et Statistiques
- Taux de réussite par filière/niveau
- Évolution des notes
- Graphiques (Chart.js)
- Export PDF/Excel

### 10. Module Paramètres
- Année académique (2025-2026, 2026-2027...)
- Périodes de notation
- Configuration des coefficients
- Backup/restore BDD

## Schéma de Base de Données

### Tables principales:

```sql
-- Utilisateurs
users(id, username, password_hash, role, nom, prenom, email, actif)

-- Années académiques
annees_academiques(id, annee, debut, fin, courant, active)

-- Instituts
instituts(id, code, nom, description, actif)

-- Filières DUT
filieres(id, code, nom, institut_id, description, active)

-- Étudiants
etudiants(id, numero, nom, prenom, date_naissance, lieu_naissance,
          sexe, nationalite, telephone, email, adresse,
          photo_path, statut, filiere_id, semestre, annee_arrivee,
          password_hash, created_at)

-- Semestres
semestres(id, code, nom, numero, filiere_id)

-- UE (Unités d'Enseignement)
ues(id, code, nom, filiere_id, semestre_id, credits, obligatoire)

-- EC (Éléments Constitutifs)
ecs(id, code, nom, ue_id, coefficient, type(examen/cc/tp))

-- Notes
notes(id, etudiant_id, ec_id, annee_academique_id, cc, tp, examen)

-- Moyennes calculées
moyennes(id, etudiant_id, semestre_id, annee_academique_id, valeur)

-- Incidents disciplinaires
incidents(id, etudiant_id, type, description, gravite, date_incident,
          utilisateur_id, mesures)

-- Absences
absences(id, etudiant_id, ec_id, date, justifie, heures)

-- Orientation
orientations(id, etudiant_id, filiere_origine, filiere_cible,
             semestre, decision, date_orientation, avis)
```

## Interface Utilisateur

### Design
- **Theme**: Professionnel, couleurs bleues/vertes (logo CUK)
- **Sidebar**: Navigation principale fixe avec instituts
- **Dashboard**: Statistiques, alertes, accès rapides
- **Tableaux**: DataTables pour lister/trier/filtrer
- **Formulaires**: Validation côté client ET serveur
- **Modals**: Pour confirmations et formulaires rapides

### Rôles et Permissions
| Rôle | Accès |
|------|-------|
| root | Tout |
| Administrateur | Tout sauf ROOT |

## Instructions de Développement

### Pour créer un nouveau module:
1. Créer la table SQL correspondante
2. Ajouter le modèle dans `src/Models/`
3. Créer le contrôleur dans `src/Controllers/`
4. Ajouter les vues dans `src/Views/`
5. Mettre à jour la navigation
6. Ajouter les routes dans le routeur

### Normes de code:
- PSR-12 pour PHP
- CamelCase pour variables/fonctions
- snake_case pour BDD
- Commentaires JSDoc pour JavaScript
- Pas de secrets hardcodés

### Sécurité:
- Prepared statements pour toutes les requêtes SQL
- CSRF tokens sur tous les formulaires
- Hash bcrypt pour mots de passe
- Validation des entrées
- Échappement XSS

## Commandes Utiles

```bash
# Installation des dépendances
composer install

# Lancement de l'application
php main.php

# Mise à jour BDD
php database/migrate.php

# Backup
php scripts/backup.php
```

## Livrables Attendus

1. Application PHPDesktop fonctionnelle (.exe Windows)
2. Base de données initialisée avec données test
3. Documentation utilisateur PDF
4. Guide d'installation

## Objectif Final

Une application desktop complète permettant:
- Gestion complète du cycle DUT
- Saisie et calcul automatique des notes
- Suivi disciplinaire
- Génération de rapports
- Interface intuitive en français
- Fonctionnement offline (pas de connexion internet requise)