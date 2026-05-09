# Guide Utilisation - CUK-Admin

## Table des matières

1. [Connexion](#connexion)
2. [Tableau de bord](#tableau-de-bord)
3. [Gestion des étudiants](#gestion-des-étudiants)
4. [Saisie des notes](#saisie-des-notes)
5. [Absences](#absences)
6. [Filières et matières](#filières-et-matières)
7. [Disciplinarité](#disciplinarité)
8. [Rapports](#rapports)

---

## Connexion

### Accéder à l'application

1. Ouvrir le navigateur
2. Entrer l'URL : `http://localhost:8000`
3. La page de connexion s'affiche

### Identifiants par défaut

| Utilisateur | Mot de passe | Rôle |
|-------------|--------------|------|
| admin | password | root |
| secretaire | password | secretaire |

> ⚠️ Changez le mot de passe après la première connexion!

### Déconnexion

Cliquez sur l'icône 🔓 en bas de la barre latérale.

---

## Tableau de bord

Le tableau de bord affiche :

- 📊 **Statistiques** : nombre d'étudiants, filières, utilisateurs
- 📈 **Graphiques** : répartition par institut, évolution des notes
- 📋 **Dernières inscriptions** : liste des nouveaux étudiants
- ⏰ **Alertes** : absences récentes, incidents

### Navigation

La barre latérale contient :

- 🏠 Tableau de bord
- 👥 Étudiants
- 📝 Notes
- 📅 Absences
- 📚 Filières & UE
- ⚠️ Disciplinarité
- 🔄 Orientations
- 📊 Rapports
- 👤 Utilisateurs (admin)
- ⚙️ Paramètres (admin)

---

## Gestion des étudiants

### Créer un nouvel étudiant

1. Menu **Étudiants**
2. Cliquer sur **Nouvel Étudiant**
3. Remplir le formulaire :
   - Nom et prénom
   - Date et lieu de naissance
   - Sexe
   - Nationalité
   - Coordonnées
   - **Institut** → sélectionner ISTPK ou ISTS
   - **Filière DUT** → sélectionner la filière
   - **Semestre** → S1, S2, S3 ou S4
4. Cliquer **Enregistrer**

### Modifier un étudiant

1. Cliquer sur l'icône ✏️ dans la colonne **Actions**
2. Modifier les informations
3. Cliquer **Enregistrer**

### Statuts disponibles

| Statut | Description |
|--------|-------------|
| Actif | Étudiant en cours |
| Redoublant | Répétition du semestre |
| Suspendu | Accès temporairement bloqué |
| Diplômé | A validé tous les crédits |
| Abandon | A quitté l'établissement |

### Filtres

- Par **Institut** : ISTPK ou ISTS
- Par **Filière** : une filière spécifique
- Par **Semestre** : S1 à S4
- Par **Statut** : actif, redoublant, etc.

---

## Saisie des notes

### Procédure

1. Menu **Notes**
2. Sélectionner un **Étudiant**
3. Choisir le **Semestre**
4. Sélectionner une **UE/Matière** (EC)
5. Saisir les notes :
   - **CC** : Contrôle Continu (0-20)
   - **TP** : Travaux Pratiques (0-20)
   - **Examen** : Examen final (0-20)
6. Cliquer **Enregistrer**

### Calcul automatique

Les moyennes sont calculées automatiquement :

```
Moyenne EC = (CC × 0.20 + TP × 0.20 + Examen × 0.60)
Moyenne UE = Moyenne pondérée des EC
Moyenne Semestre = Moyenne pondérée des UE
```

### Validation

1. Cliquer **Calculer Semestre**
2. Voir les résultats :
   - Moyenne du semestre
   - Crédits obtenus
   - Mention
   - Décision (Admis/Ajourné)

### Mentions

| Moyenne | Mention |
|---------|---------|
| ≥ 18 | Excellent |
| ≥ 16 | Très Bien |
| ≥ 14 | Bien |
| ≥ 12 | Assez Bien |
| ≥ 10 | Passable |
| < 10 | Ajourné |

---

## Absences

### Enregistrer une absence

1. Menu **Absences**
2. Section **Nouvelle Absence**
3. Sélectionner l'**Étudiant**
4. Indiquer la **Date** et le **Nombre d'heures**
5. Ajouter un **Motif** (optionnel)
6. Cocher **Justifiée** si applicable
7. Cliquer **Enregistrer**

### Justifier une absence

1. Dans la liste des absences
2. Cliquer sur le bouton 🟢 **Justifier**
3. Saisir le motif de justification
4. Valider

---

## Filières et matières

### Structure pédagogique

```
Institut (ISTPK / ISTS)
└── Filière DUT
    └── Semestre (S1, S2, S3, S4)
        └── UE (Unité d'Enseignement)
            └── EC (Élément Constitutif / Matière)
                └── Notes (CC, TP, Examen)
```

### Créer une UE

1. Menu **Filières & UE** → Onglet **UE**
2. Cliquer **Nouvelle UE**
3. Remplir :
   - Code (ex: ISTPK-IC-S1-UE1)
   - Nom (ex: Outils Mathématiques)
   - Filière DUT
   - Semestre (S1-S4)
   - Crédits (ex: 6)
4. Enregistrer

### Créer un EC (Matière)

1. Menu **Filières & UE** → Onglet **EC**
2. Cliquer **Nouvel EC**
3. Remplir :
   - Code
   - Nom
   - UE parente
   - Coefficient
   - Coefficients CC/TP/Examen
4. Enregistrer

---

## Disciplinarité

### Signaler un incident

1. Menu **Disciplinarité**
2. Section **Nouveau Signalement**
3. Remplir :
   - Étudiant concerné
   - **Type** : retard, absence, fraude, etc.
   - **Gravité** : mineur, majeur, grave
   - **Description** des faits
   - Lieu et témoins
4. Cliquer **Signaler**

### Traiter un incident

1. Dans la liste des incidents
2. Cliquer sur **Traiter** 🟡
3. Indiquer les mesures prises
4. Eventuellement une sanction
5. Valider

### Statuts des incidents

| Statut | Description |
|--------|-------------|
| En cours | Signalement reçu |
| Traité | Mesures appliquées |
| Clôturé | Dossier fermé |

---

## Rapports

### Générer un rapport

1. Menu **Rapports**
2. Sélectionner :
   - Année académique
   - Filière
   - Niveau
3. Cliquer **Générer**

### Types de rapports

- 📈 **Répartition** : graphique en camembert
- 📉 **Évolution** : courbe des notes
- 📋 **Tableau** : détaillé par filière/niveau

### Export

- **PDF** : pour impression
- **Excel** : pour analyse further

---

## Support

Pour toute question : [GitHub Issues](https://github.com/Ggboykxz/CUK-admin/issues)