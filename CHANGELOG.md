# CHANGELOG - CUK-Admin

Toutes les modifications notable de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [1.0.0] - 2025-05-09

### Ajouté

- Système complet de gestion universitaire CUK-Admin
- Base de données SQLite avec 7 filières DUT
- Support MySQL/SQLite configurable
- Authentification sécurisée avec rôles
- Module Étudiants avec gestion complète
- Module Notes avec calcul automatique des moyennes
- Module Absences avec justification
- Module Filières avec gestion Instituts, UE, EC
- Module Disciplinarité pour signalement d'incidents
- Module Orientations pour transferts
- Module Rapports avec graphiques Chart.js
- Module Utilisateurs (root, admin, secretaire, prof)
- Module Paramètres système
- Documentation complète (README, INSTALLATION, UTILISATION, ARCHITECTURE)
- Conformité PSR12 pour le code PHP
- Configuration PHPDesktop pour Windows

### Filières DUT implémentées

**Institut des Sciences et Technique Paul Kouya (ISTPK)**
- Architecture et Éco-construction
- Chimie Industrielle
- Génie Thermique et Énergies Renouvelables
- Informatique et Communication
- Productique Mécanique

**Institut des Sciences Technologiques de la Santé (ISTS)**
- Analyses Biologiques et Biochimiques
- Maintenance des Équipements Biomédicaux

### Base de données

- 18 tables relationnelles
- 7 étudiants de test
- 7 filières DUT
- 2 instituts
- 30 semestres
- 4 utilisateurs par défaut
- Notes et incidents de test

### Corrections

- Mise en conformité PSR12
- Correction des erreurs de syntaxe
- Support multi-base (MySQL/SQLite)
- Gestion des accents et utf8

---

## [0.1.0] - 2025-05-08

### Ajouté

- Structure initiale du projet
- Schéma de base de données
- Classes DatabasePHP
- Vues HTML basiques
- Configuration Composer