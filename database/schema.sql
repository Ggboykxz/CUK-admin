-- =====================================================
-- CUK-Admin: Base de données du Centre Universitaire de Koulamoutou
-- Système de gestion universitaire - Cycle DUT
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table des utilisateurs (personnel du CUK)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('root', 'administrateur', 'secretaire', 'professeur') NOT NULL DEFAULT 'secretaire',
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) UNIQUE,
    `telephone` VARCHAR(20),
    `actif` TINYINT(1) DEFAULT 1,
    `derniere_connexion` DATETIME,
    `twofa_secret` VARCHAR(255),
    `twofa_actif` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des années académiques
CREATE TABLE IF NOT EXISTS `annees_academiques` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `annee` VARCHAR(20) NOT NULL,
    `debut` DATE NOT NULL,
    `fin` DATE NOT NULL,
    `courante` TINYINT(1) DEFAULT 0,
    `inscriptions_ouvertes` TINYINT(1) DEFAULT 0,
    `notes_ouvertes` TINYINT(1) DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Instituts
CREATE TABLE IF NOT EXISTS `instituts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `nom` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `sigle` VARCHAR(20) NOT NULL,
    `responsable_id` INT UNSIGNED,
    `actif` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`responsable_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des Filières DUT
CREATE TABLE IF NOT EXISTS `filieres` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `nom` VARCHAR(200) NOT NULL,
    `nom_complet` VARCHAR(255),
    `description` TEXT,
    `institut_id` INT UNSIGNED NOT NULL,
    `responsable_id` INT UNSIGNED,
    `duree_ans` TINYINT(1) DEFAULT 2,
    `credits_total` INT DEFAULT 120,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`institut_id`) REFERENCES `instituts`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`responsable_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des semestres
CREATE TABLE IF NOT EXISTS `semestres` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `nom` VARCHAR(50) NOT NULL,
    `numero` TINYINT(2) NOT NULL,
    `filiere_id` INT UNSIGNED NOT NULL,
    `credits` INT DEFAULT 30,
    `active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`filiere_id`) REFERENCES `filieres`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des étudiants
CREATE TABLE IF NOT EXISTS `etudiants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero` VARCHAR(20) NOT NULL UNIQUE,
    `matricule` VARCHAR(20) UNIQUE,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `sexe` ENUM('M', 'F') NOT NULL,
    `date_naissance` DATE NOT NULL,
    `lieu_naissance` VARCHAR(150),
    `nationalite` VARCHAR(50) DEFAULT 'Gabonaise',
    `telephone` VARCHAR(20),
    `email` VARCHAR(150),
    `adresse` TEXT,
    `photo_path` VARCHAR(255),
    `cni_path` VARCHAR(255),
    `bac_path` VARCHAR(255),
    `diplome_path` VARCHAR(255),
    `filiere_id` INT UNSIGNED NOT NULL,
    `semestre` VARCHAR(5) NOT NULL DEFAULT 'S1',
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `date_inscription` DATE NOT NULL,
    `statut` ENUM('actif', 'suspendu', 'diplome', 'abandon', 'exclu', 'redoublant') DEFAULT 'actif',
    `boursier` TINYINT(1) DEFAULT 0,
    `type_bourse` VARCHAR(50),
    `password_hash` VARCHAR(255),
    `observation` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`filiere_id`) REFERENCES `filieres`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des UE (Unités d'Enseignement)
CREATE TABLE IF NOT EXISTS `ues` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `filiere_id` INT UNSIGNED NOT NULL,
    `semestre_id` INT UNSIGNED NOT NULL,
    `credits` DECIMAL(4,1) NOT NULL DEFAULT 0,
    `obligatoire` TINYINT(1) DEFAULT 1,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`filiere_id`) REFERENCES `filieres`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`semestre_id`) REFERENCES `semestres`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_ue` (`code`, `filiere_id`, `semestre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des EC (Éléments Constitutifs)
CREATE TABLE IF NOT EXISTS `ecs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL,
    `nom` VARCHAR(200) NOT NULL,
    `ue_id` INT UNSIGNED NOT NULL,
    `coefficient` DECIMAL(4,1) NOT NULL DEFAULT 1,
    `coefficient_cc` DECIMAL(3,2) DEFAULT 0.20,
    `coefficient_tp` DECIMAL(3,2) DEFAULT 0.20,
    `coefficient_examen` DECIMAL(3,2) DEFAULT 0.60,
    `type` ENUM('theorique', 'pratique', 'tp', 'mixed') DEFAULT 'mixed',
    `vh_cours` INT DEFAULT 0,
    `vh_td` INT DEFAULT 0,
    `vh_tp` INT DEFAULT 0,
    `vh_total` INT GENERATED ALWAYS AS (IFNULL(`vh_cours`,0) + IFNULL(`vh_td`,0) + IFNULL(`vh_tp`,0)) STORED,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ue_id`) REFERENCES `ues`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_ec` (`code`, `ue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des enseignants
CREATE TABLE IF NOT EXISTS `utilisateurs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `matricule` VARCHAR(20) UNIQUE,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `sexe` ENUM('M', 'F') NOT NULL,
    `date_naissance` DATE,
    `lieu_naissance` VARCHAR(150),
    `nationalite` VARCHAR(50) DEFAULT 'Gabonaise',
    `telephone` VARCHAR(20),
    `email` VARCHAR(150),
    `adresse` TEXT,
    `photo_path` VARCHAR(255),
    `grade` VARCHAR(50),
    `specialite` VARCHAR(150),
    `diplome` VARCHAR(255),
    `institut_id` INT UNSIGNED,
    `actif` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`institut_id`) REFERENCES `instituts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table d'assignation des enseignants aux EC
CREATE TABLE IF NOT EXISTS `enseigner` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `ec_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `type_heures` ENUM('CM', 'TD', 'TP') DEFAULT 'CM',
    `volume_horaire` INT DEFAULT 0,
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ec_id`) REFERENCES `ecs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_enseignement` (`utilisateur_id`, `ec_id`, `annee_academique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notes
CREATE TABLE IF NOT EXISTS `notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `ec_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `cc` DECIMAL(4,2),
    `tp` DECIMAL(4,2),
    `examen` DECIMAL(4,2),
    `moyenne_ec` DECIMAL(4,2),
    `observation` VARCHAR(255),
    `date_saisie` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `saisi_par` INT UNSIGNED,
    `valide` TINYINT(1) DEFAULT 0,
    `date_validation` DATETIME,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ec_id`) REFERENCES `ecs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`saisi_par`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_note` (`etudiant_id`, `ec_id`, `annee_academique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des moyennes calculées par semestre
CREATE TABLE IF NOT EXISTS `moyennes_semestrielles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `semestre_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `moyenne_semestre` DECIMAL(4,2),
    `credits_obtenus` INT DEFAULT 0,
    `total_credits` INT DEFAULT 0,
    `validation` ENUM('valide', 'ajourne', 'non_valide') DEFAULT 'non_valide',
    `mention_semestre` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`semestre_id`) REFERENCES `semestres`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_moyenne_sem` (`etudiant_id`, `semestre_id`, `annee_academique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des résultats annuels (moyenne DUT)
CREATE TABLE IF NOT EXISTS `resultats_annuels` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `moyenne_annuelle` DECIMAL(4,2),
    `credits_obtenus` INT DEFAULT 0,
    `total_credits` INT DEFAULT 0,
    `decision` ENUM('admis', 'ajourne', 'redoublant', 'exclu', 'diplome') NOT NULL,
    `mention` VARCHAR(50),
    `rang` INT,
    `pourcentage_reussite` DECIMAL(5,2),
    `observation` TEXT,
    `date_jury` DATE,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_resultat` (`etudiant_id`, `annee_academique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des incidents disciplinaires
CREATE TABLE IF NOT EXISTS `incidents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `type` ENUM('retard', 'absence', 'fraude', 'triche', 'violence', 'vandalisme', 'non_paiement', 'autre') NOT NULL,
    `description` TEXT NOT NULL,
    `gravite` ENUM('mineur', 'majeur', 'grave') NOT NULL DEFAULT 'mineur',
    `date_incident` DATE NOT NULL,
    `lieu` VARCHAR(150),
    `temoin` VARCHAR(255),
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `mesures` TEXT,
    `sanction` VARCHAR(255),
    `date_mesures` DATE,
    `statut` ENUM('en_cours', 'traite', 'cloture') DEFAULT 'en_cours',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`utilisateur_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des absences
CREATE TABLE IF NOT EXISTS `absences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `ec_id` INT UNSIGNED,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `date_absence` DATE NOT NULL,
    `nombre_heures` INT DEFAULT 2,
    `justifiee` TINYINT(1) DEFAULT 0,
    `motif` VARCHAR(255),
    `document_justificatif` VARCHAR(255),
    `saisi_par` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ec_id`) REFERENCES `ecs`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`saisi_par`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des orientations et transferts
CREATE TABLE IF NOT EXISTS `orientations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `filiere_origine_id` INT UNSIGNED,
    `filiere_cible_id` INT UNSIGNED,
    `semestre_origine` VARCHAR(5),
    `semestre_cible` VARCHAR(5),
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `type` ENUM('orientation', 'transfert', 'reorientation', 'passage_licence') NOT NULL,
    `decision` ENUM('accepte', 'refuse', 'en_attente', 'report') NOT NULL DEFAULT 'en_attente',
    `mention` VARCHAR(50),
    `rang` INT,
    `avis_enseignant` TEXT,
    `avis_conseil` TEXT,
    `date_orientation` DATE,
    `date_decision` DATE,
    `utilisateur_id` INT UNSIGNED,
    `observation` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`filiere_origine_id`) REFERENCES `filieres`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`filiere_cible_id`) REFERENCES `filieres`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`utilisateur_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des PV de jury
CREATE TABLE IF NOT EXISTS `pvs_jury` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `semestre` VARCHAR(5) NOT NULL,
    `filiere_id` INT UNSIGNED,
    `date_jury` DATE NOT NULL,
    `lieu` VARCHAR(150),
    `president` VARCHAR(150),
    `secretaire` VARCHAR(150),
    `membres` TEXT,
    `observations` TEXT,
    `file_path` VARCHAR(255),
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`filiere_id`) REFERENCES `filieres`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des bulletins
CREATE TABLE IF NOT EXISTS `bulletins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `semestre` VARCHAR(5) NOT NULL,
    `moyenne_generale` DECIMAL(4,2),
    `credits_obtenus` INT DEFAULT 0,
    `mention` VARCHAR(50),
    `rang` INT,
    `appreciation` TEXT,
    `file_path` VARCHAR(255),
    `genere_le` DATETIME,
    `signe_par` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`signe_par`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_bulletin` (`etudiant_id`, `annee_academique_id`, `semestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paramètres système
CREATE TABLE IF NOT EXISTS `parametres` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cle` VARCHAR(100) NOT NULL UNIQUE,
    `valeur` TEXT,
    `description` VARCHAR(255),
    `categorie` VARCHAR(50) DEFAULT 'general',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table du journal d'activité
CREATE TABLE IF NOT EXISTS `journal_activite` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `table_concernee` VARCHAR(100),
    `id_concerne` INT UNSIGNED,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `type` VARCHAR(50) NOT NULL,
    `titre` VARCHAR(200) NOT NULL,
    `message` TEXT,
    `lien` VARCHAR(255),
    `lu` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des salles
CREATE TABLE IF NOT EXISTS `salles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `nom` VARCHAR(200) NOT NULL,
    `capacite` INT DEFAULT 30,
    `batiment` VARCHAR(100),
    `etage` INT DEFAULT 0,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des cours/emploi du temps
CREATE TABLE IF NOT EXISTS `cours` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ec_id` INT UNSIGNED NOT NULL,
    `enseignant_id` INT UNSIGNED,
    `salle` VARCHAR(20),
    `jour_semaine` INT NOT NULL DEFAULT 1,
    `heure_debut` TIME NOT NULL,
    `heure_fin` TIME NOT NULL,
    `type_seance` VARCHAR(10) DEFAULT 'CM',
    `groupe` VARCHAR(50),
    `semestre` VARCHAR(5) DEFAULT 'S1',
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ec_id`) REFERENCES `ecs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des messages internes
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT UNSIGNED NOT NULL,
    `recipient_id` INT UNSIGNED,
    `subject` VARCHAR(255) NOT NULL,
    `body` TEXT,
    `parent_id` INT UNSIGNED,
    `read_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des frais de scolarité
CREATE TABLE IF NOT EXISTS `frais_scolarite` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `etudiant_id` INT UNSIGNED NOT NULL,
    `annee_academique_id` INT UNSIGNED NOT NULL,
    `montant_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `montant_paye` DECIMAL(10,2) DEFAULT 0,
    `statut` VARCHAR(20) DEFAULT 'impaye',
    `echeance` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;