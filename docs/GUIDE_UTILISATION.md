# 📘 CUK-Admin — Guide d'Utilisation Complet

**Centre Universitaire de Koulamoutou — Système de Gestion Universitaire**
Version 2.0 — Mai 2026

---

## Table des matières

1. [Présentation](#1-présentation)
2. [Installation](#2-installation)
3. [Première connexion](#3-première-connexion)
4. [Tableau de bord](#4-tableau-de-bord)
5. [Module Étudiants](#5-module-étudiants)
6. [Module Notes](#6-module-notes)
7. [Module Absences](#7-module-absences)
8. [Module Filières & UE](#8-module-filières--ue)
9. [Module Disciplinarité](#9-module-disciplinarité)
10. [Module Orientations](#10-module-orientations)
11. [Module Rapports](#11-module-rapports)
12. [Module Utilisateurs](#12-module-utilisateurs)
13. [Module Paramètres](#13-module-paramètres)
14. [Module Emploi du temps](#14-module-emploi-du-temps)
15. [Module Finances](#15-module-finances)
16. [Module Messagerie](#16-module-messagerie)
17. [Module Jury](#17-module-jury)
18. [Portail Étudiant](#18-portail-étudiant)
19. [Sauvegarde et Restauration](#19-sauvegarde-et-restauration)
20. [Sécurité](#20-sécurité)
21. [Dépannage](#21-dépannage)

---

## 1. Présentation

CUK-Admin est une application de gestion universitaire conçue pour le **Centre Universitaire de Koulamoutou (CUK)** au Gabon. Elle gère l'ensemble du cycle **DUT (Diplôme Universitaire de Technologie)** — un programme de 2 ans (120 crédits) réparti sur 4 semestres (S1 à S4).

### Instituts gérés

| Institut | Sigle | Filières |
|---|---|---|
| Institut des Sciences et Technique Paul Kouya | **ISTPK** | Architecture, Chimie Industrielle, Génie Thermique, Informatique, Productique Mécanique |
| Institut des Sciences Technologiques de la Santé | **ISTS** | Analyses Biologiques, Maintenance Biomédicale |

### Rôles utilisateurs

| Rôle | Droits |
|---|---|
| **root** | Accès total à toutes les fonctionnalités |
| **administrateur** | Tout sauf créer des comptes root |
| **secrétaire** | Gestion des étudiants, notes, absences, discipline |
| **professeur** | Consultation uniquement, saisie des notes |

### Technologies

- PHP 8.2+, SQLite (défaut) ou MySQL/MariaDB
- Redis pour les sessions (production)
- Bootstrap 5.3, Chart.js, DataTables, jQuery
- Dompdf (exports PDF), PhpSpreadsheet (exports Excel)

---

## 2. Installation

### 2.1 Installation rapide (Docker — recommandé)

```bash
# 1. Cloner le projet
git clone https://github.com/Ggboykxz/CUK-admin.git
cd CUK-admin

# 2. Copier et configurer l'environnement
cp .env.example .env
# Modifier les mots de passe : nano .env

# 3. Générer les certificats SSL
make prod-ssl

# 4. Lancer la stack complète
make prod

# 5. Accéder à l'application
# https://localhost
```

### 2.2 Installation manuelle (développement)

```bash
# 1. Prérequis : PHP 8.2+, Composer
composer install --no-dev --optimize-autoloader

# 2. Lancer le serveur
php -S localhost:8000

# 3. Ouvrir dans le navigateur
# http://localhost:8000
```

### 2.3 Installation avec MySQL

```bash
# 1. Exécuter l'installateur CLI
php installer/install.php

# 2. Suivre les instructions :
#   Hôte MySQL [localhost]:
#   Port [3306]:
#   Utilisateur [root]:
#   Mot de passe:
#   Nom de la base [cuk_admin]:
```

L'installateur crée la base de données, importe le schéma et les données initiales, et met à jour la configuration.

### 2.4 Exécuter les migrations

```bash
php database/migrate.php migrate
```

---

## 3. Première connexion

### 3.1 Comptes par défaut

| Identifiant | Mot de passe | Rôle |
|---|---|---|
| `admin` | `password` | **root** |
| `secretaire` | `password` | secrétaire |
| `prof_ngouala` | `password` | professeur |
| `prof_mouyama` | `password` | professeur |

### 3.2 Changement de mot de passe obligatoire

**⚠️ IMPORTANT :** À la première connexion avec un compte utilisant le mot de passe par défaut, le système vous redirige automatiquement vers la page de changement de mot de passe.

**Exigences :**
- Minimum 8 caractères
- Au moins 1 lettre majuscule
- Au moins 1 chiffre
- Ne peut pas être identique à l'ancien

### 3.3 Interface

Une fois connecté, l'interface se compose de :
- **Barre latérale gauche** : navigation principale
- **Barre supérieure** : titre de la page, recherche globale
- **Zone centrale** : contenu de la page active

---

## 4. Tableau de bord

La page d'accueil affiche un résumé de l'activité :

- **Statistiques** : nombre total d'étudiants, étudiants actifs, filières, utilisateurs
- **Effectifs par institut** : répartition ISTPK / ISTS avec barres de progression
- **Absences récentes** : liste des dernières absences enregistrées
- **Dernières inscriptions** : tableau des 5 derniers étudiants inscrits

---

## 5. Module Étudiants

### 5.1 Liste des étudiants

Affiche tous les étudiants dans un tableau interactif avec :
- Recherche par mot-clé
- Filtres : Institut, Filière, Semestre, Statut
- Tri par colonnes
- Pagination

### 5.2 Créer un étudiant

1. Cliquer sur **"Nouvel Étudiant"**
2. Remplir le formulaire :
   - **Nom**, **Prénom** (obligatoires)
   - **Sexe** (Masculin/Féminin)
   - **Date de naissance**
   - **Nationalité** (défaut: Gabonaise)
   - **Lieu de naissance**, **Téléphone**, **Email**, **Adresse**
   - **Institut** → sélectionner → la liste des filières se charge automatiquement
   - **Filière DUT**
   - **Semestre** (S1-S4)
   - **Boursier** (case à cocher)
3. Cliquer sur **"Enregistrer"**

Le numéro étudiant est généré automatiquement (ex: `ETU-2026-042`).

### 5.3 Modifier un étudiant

Cliquer sur l'icône ✏️ dans la colonne Actions d'un étudiant.

### 5.4 Voir le profil complet

Cliquer sur l'icône 👁️ pour afficher :
- Informations personnelles
- Photo (possibilité d'uploader une photo)
- **Relevé de notes PDF** 📄
- **Bulletin PDF** 📄

### 5.5 Supprimer un étudiant

Cliquer sur l'icône 🗑️ et confirmer la suppression.

### 5.6 Importer des étudiants (CSV)

1. Cliquer sur le bouton **"Import CSV"** dans la page
2. Sélectionner un fichier CSV avec les colonnes :
   ```
   nom,prenom,sexe,date_naissance,lieu_naissance,nationalite,telephone,email,adresse,filiere_code,semestre,boursier
   ```
3. Cliquer sur **"Importer"**
4. Les étudiants sont créés avec des numéros automatiques

### 5.7 Upload de photo

Dans le profil d'un étudiant, cliquer sur le bouton **"Photo"** et sélectionner une image (JPG, PNG, GIF, WebP — max 2 Mo).

---

## 6. Module Notes

### 6.1 Saisie individuelle des notes

1. **Sélectionner un étudiant** dans la liste déroulante
2. **Choisir le semestre** (S1-S4)
3. Cliquer sur **"Charger"** pour afficher les matières (EC)
4. **Sélectionner une matière (EC)**
5. Saisir les notes :
   - **CC** (Contrôle Continu, /20)
   - **TP** (Travaux Pratiques, /20)
   - **Examen** (/20)
6. Cliquer sur **"Enregistrer"**

La moyenne est calculée automatiquement selon les coefficients de la matière.

### 6.2 Saisie groupée des notes

Permet de saisir toutes les notes d'un étudiant pour un semestre en une seule vue :

1. Sélectionner l'étudiant et le semestre
2. Cliquer sur **"Saisie groupée"**
3. Un tableau affiche tous les ECs du semestre
4. Saisir CC, TP, Examen pour chaque matière
5. Cliquer sur **"Tout enregistrer"**

### 6.3 Calcul du semestre

1. Cliquer sur **"Calculer Semestre"**
2. Le système affiche :
   - Les résultats par UE (moyenne, validation)
   - La moyenne du semestre
   - Les crédits obtenus
   - La mention
   - La décision (admis/ajourné)

### 6.4 Sauvegarder la moyenne semestrielle

Après le calcul, cliquer sur **"Sauvegarder moyenne"** pour enregistrer le résultat dans la base de données (table `moyennes_semestrielles`).

### 6.5 Validation des notes (Admin)

Les administrateurs peuvent valider les notes saisies par les professeurs. Une note validée est verrouillée et ne peut plus être modifiée.

### 6.6 Calcul des moyennes

**Formules :**

```
Moyenne EC = (CC × coef_CC + TP × coef_TP + Examen × coef_Examen) / (coef_CC + coef_TP + coef_Examen)
Moyenne UE = Σ(Moyenne_EC × coefficient_EC) / Σ(coefficient_EC)
Moyenne Semestre = Σ(Moyenne_UE × crédits_UE) / Σ(crédits_UE)
```

**Validation :** Une UE est validée si sa moyenne ≥ 10/20.

**Mentions :**

| Moyenne | Mention |
|---|---|
| ≥ 18 | Excellent |
| ≥ 16 | Très Bien |
| ≥ 14 | Bien |
| ≥ 12 | Assez Bien |
| ≥ 10 | Passable |
| < 10 | Ajourné |

---

## 7. Module Absences

### 7.1 Enregistrer une absence

1. Cliquer sur l'onglet **"Absences"**
2. Remplir le formulaire :
   - **Étudiant** (recherche dans la liste)
   - **Date**
   - **Nombre d'heures** (défaut: 2h)
   - **Motif** (optionnel)
   - **Justifiée** (case à cocher)
3. Cliquer sur **"Enregistrer"**

### 7.2 Justifier une absence

1. Dans la liste, cliquer sur l'icône ✓ d'une absence non justifiée
2. Saisir le motif de justification
3. Cliquer sur **"Confirmer"**

### 7.3 Appel / Roll call

L'onglet **"Appel"** permet de faire l'appel pour une classe entière :

1. **Sélectionner la filière** et le **semestre**
2. Cliquer sur **"Charger"** → la liste des étudiants apparaît
3. Cocher/décocher les présents
4. Utiliser **"Tous présents"** ou **"Tous absents"** pour gagner du temps
5. Cliquer sur **"Enregistrer l'appel"**

Les étudiants marqués absents reçoivent automatiquement une entrée dans le registre des absences.

### 7.4 Statistiques

Le haut de la page affiche :
- Total des absences
- Absences justifiées
- Absences non justifiées

---

## 8. Module Filières & UE

### 8.1 Structure académique

```
Institut (ISTPK / ISTS)
  └── Filière DUT (ex: Informatique et Communication)
       └── Semestre (S1, S2, S3, S4) — 30 crédits chacun
            └── UE (Unité d'Enseignement)
                 └── EC (Élément Constitutif = matière)
```

### 8.2 Créer un institut

1. Onglet **"Instituts"**
2. Remplir Code, Sigle, Nom complet
3. Cliquer sur **"Créer"**

### 8.3 Créer une filière DUT

1. Onglet **"Filières DUT"**
2. Remplir Code, Nom, Institut, Durée, Crédits
3. Cliquer sur **"Créer"** → les 4 semestres sont générés automatiquement (S1-S4, 30 crédits chacun)

### 8.4 Créer une UE

1. Onglet **"UE"**
2. Remplir Code, Nom, Filière, Semestre, Crédits
3. Cliquer sur **"Créer"**

### 8.5 Créer un EC (matière)

1. Onglet **"EC"**
2. Remplir Code, Nom, UE parente, Coefficient, Coefficients CC/TP/Examen
3. Cliquer sur **"Créer"**

---

## 9. Module Disciplinarité

### 9.1 Signaler un incident

1. Remplir le formulaire :
   - **Étudiant** concerné
   - **Date** de l'incident
   - **Type** : Retard, Absence injustifiée, Fraude, Triche, Violence, Vandalisme, Non-paiement, Autre
   - **Gravité** : Mineur, Majeur, Grave
   - **Lieu**, **Témoin(s)**
   - **Description des faits**
2. Cliquer sur **"Signaler"**

### 9.2 Traiter un incident

Cliquer sur l'icône ✓ dans la liste pour :
- Enregistrer les **mesures prises**
- Définir une **sanction** (si applicable)
- Le statut passe à "traité"

### 9.3 Clôturer un incident

Une fois traité, cliquer sur l'icône ✓✓ pour clôturer définitivement.

### 9.4 Statistiques

- Total des incidents
- Incidents en cours
- Incidents graves

---

## 10. Module Orientations

### 10.1 Créer une orientation

1. Remplir :
   - **Étudiant**
   - **Type** : Orientation, Transfert, Réorientation
   - **Filière cible**
   - Mention, Rang, Avis de l'enseignant
2. Cliquer sur **"Enregistrer"**

### 10.2 Décider une orientation

1. Cliquer sur **"Décider"** dans la liste
2. Choisir la décision : Accepté, Refusé, Reporté
3. Ajouter l'avis du conseil
4. Cliquer sur **"Valider"**

Si la décision est "Accepté", la filière de l'étudiant est automatiquement mise à jour.

---

## 11. Module Rapports

### 11.1 Générer des rapports

1. Sélectionner les filtres (année, filière)
2. Cliquer sur un des boutons :
   - **"Générer"** → PDF de la liste des étudiants
   - **"PDF"** → Export PDF
   - **"Excel"** → Export Excel

### 11.2 Graphiques

- **Répartition par filière** (graphique doughnut)
- **Évolution des notes** (graphique linéaire par mois)

---

## 12. Module Utilisateurs

**Réservé aux rôles root et administrateur.**

### 12.1 Créer un utilisateur

1. Remplir le formulaire :
   - Nom d'utilisateur, Mot de passe
   - Rôle (root, administrateur, secrétaire, professeur)
   - Nom, Prénom, Email, Téléphone
2. Cliquer sur **"Créer"**

### 12.2 Journal d'activité

Le deuxième onglet affiche les 50 dernières actions réalisées dans l'application (connexions, créations, modifications, suppressions).

---

## 13. Module Paramètres

**Réservé aux rôles root et administrateur.**

### 13.1 Informations de l'établissement

Modifier le nom, l'adresse, le téléphone et l'email de l'établissement.

### 13.2 Années académiques

- Créer une nouvelle année académique
- Définir l'année courante

### 13.3 Authentification à deux facteurs (2FA)

1. Cliquer sur **"Activer"** dans la section 2FA
2. Scannez le QR code avec Google Authenticator ou Authy
3. Saisir le code à 6 chiffres
4. Cliquer sur **"Vérifier"**

### 13.4 Sauvegarde et restauration

- **Créer une sauvegarde** : copie immédiate de la base de données
- **Lister les sauvegardes** : visualiser les backups disponibles
- **Restaurer** : restaurer une version précédente (⚠️ irréversible)
- **Supprimer** : supprimer une sauvegarde obsolète

Les sauvegardes automatiques sont créées quotidiennement par le scheduler (7 jours de rétention).

---

## 14. Module Emploi du temps

### 14.1 Planning hebdomadaire

Affichage des cours sous forme de tableau hebdomadaire (lundi à vendredi, 8h-18h).

### 14.2 Ajouter un cours

1. Cliquer sur **"Ajouter un cours"**
2. Remplir :
   - **EC** (matière)
   - **Enseignant**
   - **Jour**, **Heure début**, **Heure fin**
   - **Salle**
   - **Type** (CM, TD, TP)
   - **Semestre**, **Groupe**
3. Cliquer sur **"Enregistrer"**

### 14.3 Visualiser un cours

Cliquer sur un cours dans le planning pour voir les détails.

---

## 15. Module Finances

### 15.1 Définir les frais de scolarité

1. Onglet **"Définir frais"**
2. Sélectionner l'étudiant
3. Saisir le montant total et les échéances
4. Cliquer sur **"Enregistrer"**

### 15.2 Enregistrer un paiement

1. Dans la liste, cliquer sur **"Payer"**
2. Saisir le montant et le mode de paiement
3. Cliquer sur **"Confirmer le paiement"**
4. Le statut se met à jour automatiquement : impaye → partiel → paye

### 15.3 Modes de paiement

- Espèces
- Chèque
- Virement
- Mobile Money
- Carte bancaire

---

## 16. Module Messagerie

### 16.1 Boîte de réception

Affiche les messages reçus avec :
- Sujet, expéditeur, date
- Indicateur de lecture (gras = non lu)
- Actions : marquer comme lu, répondre

### 16.2 Envoyer un message

1. Cliquer sur **"Nouveau message"**
2. Sélectionner le destinataire
3. Saisir le sujet et le message
4. Cliquer sur **"Envoyer"**

### 16.3 Messages envoyés

L'onglet **"Envoyés"** affiche l'historique des messages envoyés.

---

## 17. Module Jury

### 17.1 Créer un PV de jury

1. Cliquer sur **"Nouveau PV"**
2. Remplir :
   - **Filière**, **Semestre**
   - **Date du jury**, **Lieu**
   - **Président**, **Secrétaire**, **Membres**
   - **Observations**
3. Option : cocher **"Générer les résultats"** pour calculer automatiquement admis/ajourné

### 17.2 Génération des résultats

La fonction "Générer les résultats" :
1. Récupère tous les étudiants actifs de la filière/semestre
2. Calcule les moyennes semestrielles
3. Détermine la décision (admis si moyenne ≥ 10)
4. Enregistre dans `resultats_annuels`

---

## 18. Portail Étudiant

### 18.1 Accès

Le portail étudiant est accessible via `?page=portal` ou depuis le lien dans la barre latérale (s'ouvre dans un nouvel onglet).

### 18.2 Connexion

1. L'étudiant saisit son **numéro** (ex: ETU-2026-042)
2. Saisit son **mot de passe** (défini par l'administration)
3. Clique sur **"Se connecter"**

### 18.3 Fonctionnalités

**Accueil :**
- Statistiques personnelles (notes, absences, semestres)
- Informations de profil

**Mes notes :**
- Tableau complet des notes par UE/EC
- Coefficients, CC, TP, Examen, moyenne

**Mes absences :**
- Historique des absences
- Statut (justifiée/non)
- Motif

**Mes relevés :**
- Téléchargement du **relevé de notes** PDF
- Téléchargement du **bulletin** PDF
- Résultats semestriels (moyenne, crédits, validation, mention)

---

## 19. Sauvegarde et Restauration

### 19.1 Sauvegarde manuelle

Via l'interface Paramètres > onglet Sauvegarde > **"Créer une sauvegarde"**

### 19.2 Sauvegarde automatique

Le scheduler (`scheduler/tasks.php`) crée une sauvegarde automatique chaque jour à minuit. Les 7 dernières sauvegardes sont conservées.

### 19.3 Restauration

⚠️ **Cette action est irréversible.** La base de données actuelle est remplacée par la sauvegarde.

Via l'interface : Paramètres > Sauvegarde > cliquer sur l'icône de restauration à côté d'un fichier de backup.

### 19.4 API de backup

```bash
# Lister les sauvegardes
curl https://votre-domaine/api/backup.php?action=list

# Créer une sauvegarde
curl https://votre-domaine/api/backup.php?action=create

# Restaurer
curl https://votre-domaine/api/backup.php?action=restore&file=backup_2026-05-12_120000.sqlite
```

---

## 20. Sécurité

### 20.1 Mesures implémentées

| Mesure | Détail |
|---|---|
| **CSRF** | Token sur tous les formulaires et requêtes AJAX |
| **XSS** | `htmlspecialchars()` sur toutes les sorties, `JSON_HEX_TAG` sur les données JSON |
| **Content Security Policy** | Nonce-based, pas de `unsafe-inline` |
| **HSTS** | HTTPS forcé, preload |
| **Rate limiting** | 5 tentatives de connexion / 5 minutes |
| **Verrouillage compte** | 10 échecs → 15 minutes de blocage |
| **Session** | HttpOnly, SameSite=Lax, strict_mode, Redis en production |
| **Mots de passe** | Bcrypt, changement forcé si password par défaut |
| **2FA** | TOTP (Google Authenticator, Authy) |
| **Headers** | X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy |

### 20.2 Recommandations

- **Changer tous les mots de passe par défaut** immédiatement après l'installation
- **Utiliser HTTPS** en production (certificat Let's Encrypt)
- **Activer le 2FA** pour les comptes root et administrateur
- **Sauvegarder régulièrement** la base de données
- **Restreindre l'accès** à la page d'administration (paramètres, utilisateurs)

---

## 21. Dépannage

### 21.1 Problèmes courants

**L'application ne se lance pas**
```bash
# Vérifier que PHP est installé
php -v

# Vérifier les permissions
chmod -R 755 runtime/ database/ uploads/
```

**Erreur "Base de données non trouvée"**
```bash
# Créer le fichier SQLite vide
touch database/cuk_admin.sqlite
chmod 666 database/cuk_admin.sqlite
```

**Page blanche / erreur 500**
```bash
# Voir les logs
tail -f runtime/logs/*.log

# Vérifier les erreurs PHP
php -l index.php
```

**Export PDF ne fonctionne pas**
```bash
# Installer les dépendances
composer install --no-dev
```

**Oubli du mot de passe admin**
```bash
# Réinitialiser via la base SQLite
sqlite3 database/cuk_admin.sqlite
UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
.quit
# Nouveau mot de passe : "password" (à changer à la connexion)
```

### 21.2 Commandes utiles

```bash
make prod       # Lancer la production (Docker)
make dev        # Lancer le serveur de développement
make test       # Exécuter les tests
make logs       # Voir les logs en temps réel
make migrate    # Exécuter les migrations
make backup     # Sauvegarde manuelle
make prod-ssl   # Générer les certificats SSL
```

### 21.3 Structure des logs

```
runtime/logs/
├── 2026-05-12.log      # Logs du jour
├── 2026-05-11.log      # Logs de la veille
├── access.log          # Logs d'accès (IP, URL, utilisateur)
└── ...                 # Rotation automatique (30 jours)
```

### 21.4 Support

Pour toute question ou problème :
- **Documentation technique** : `docs/` et `AGENTS.md`
- **Rapport de bug** : https://github.com/Ggboykxz/CUK-admin/issues
- **Email** : contact@cuk-gabon.ga

---

*Document généré le 12 mai 2026 — CUK-Admin v2.0*
