-- =====================================================
-- CUK-Admin: Base de données du Centre Universitaire de Koulamoutou
-- Système de gestion universitaire - Cycle DUT
-- =====================================================

;
;

-- Table des utilisateurs (personnel du CUK)
CREATE TABLE IF NOT EXISTS "users" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "username" VARCHAR(50) NOT NULL UNIQUE,
    "password_hash" VARCHAR(255) NOT NULL,
    "role" VARCHAR(50) NOT NULL DEFAULT 'secretaire',
    "nom" VARCHAR(100) NOT NULL,
    "prenom" VARCHAR(100) NOT NULL,
    "email" VARCHAR(150) UNIQUE,
    "telephone" VARCHAR(20),
    "actif" INTEGER DEFAULT 1,
    "derniere_connexion" DATETIME,
    "twofa_secret" VARCHAR(255),
    "twofa_actif" INTEGER DEFAULT 0,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
)   ;

-- Table des années académiques
CREATE TABLE IF NOT EXISTS "annees_academiques" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "annee" VARCHAR(20) NOT NULL,
    "debut" DATE NOT NULL,
    "fin" DATE NOT NULL,
    "courante" INTEGER DEFAULT 0,
    "inscriptions_ouvertes" INTEGER DEFAULT 0,
    "notes_ouvertes" INTEGER DEFAULT 0,
    "active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)   ;

-- Table des Instituts
CREATE TABLE IF NOT EXISTS "instituts" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(20) NOT NULL UNIQUE,
    "nom" VARCHAR(200) NOT NULL,
    "description" TEXT,
    "sigle" VARCHAR(20) NOT NULL,
    "responsable_id" INT UNSIGNED,
    "actif" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("responsable_id") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des Filières DUT
CREATE TABLE IF NOT EXISTS "filieres" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(20) NOT NULL UNIQUE,
    "nom" VARCHAR(200) NOT NULL,
    "nom_complet" VARCHAR(255),
    "description" TEXT,
    "institut_id" INT UNSIGNED NOT NULL,
    "responsable_id" INT UNSIGNED,
    "duree_ans" INTEGER DEFAULT 2,
    "credits_total" INT DEFAULT 120,
    "active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("institut_id") REFERENCES "instituts"("id") ON DELETE RESTRICT,
    FOREIGN KEY ("responsable_id") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des semestres
CREATE TABLE IF NOT EXISTS "semestres" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(10) NOT NULL UNIQUE,
    "nom" VARCHAR(50) NOT NULL,
    "numero" TINYINTEGER NOT NULL,
    "filiere_id" INT UNSIGNED NOT NULL,
    "credits" INT DEFAULT 30,
    "active" INTEGER DEFAULT 1,
    FOREIGN KEY ("filiere_id") REFERENCES "filieres"("id") ON DELETE CASCADE
)   ;

-- Table des étudiants
CREATE TABLE IF NOT EXISTS "etudiants" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "numero" VARCHAR(20) NOT NULL UNIQUE,
    "matricule" VARCHAR(20) UNIQUE,
    "nom" VARCHAR(100) NOT NULL,
    "prenom" VARCHAR(100) NOT NULL,
    "sexe" VARCHAR(50) NOT NULL,
    "date_naissance" DATE NOT NULL,
    "lieu_naissance" VARCHAR(150),
    "nationalite" VARCHAR(50) DEFAULT 'Gabonaise',
    "telephone" VARCHAR(20),
    "email" VARCHAR(150),
    "adresse" TEXT,
    "photo_path" VARCHAR(255),
    "cni_path" VARCHAR(255),
    "bac_path" VARCHAR(255),
    "diplome_path" VARCHAR(255),
    "filiere_id" INT UNSIGNED NOT NULL,
    "semestre" VARCHAR(5) NOT NULL DEFAULT 'S1',
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "date_inscription" DATE NOT NULL,
    "statut" VARCHAR(50) DEFAULT 'actif',
    "boursier" INTEGER DEFAULT 0,
    "type_bourse" VARCHAR(50),
    "password_hash" VARCHAR(255),
    "observation" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY ("filiere_id") REFERENCES "filieres"("id") ON DELETE RESTRICT,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE RESTRICT
)   ;

-- Table des UE (Unités d'Enseignement)
CREATE TABLE IF NOT EXISTS "ues" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(20) NOT NULL,
    "nom" VARCHAR(200) NOT NULL,
    "description" TEXT,
    "filiere_id" INT UNSIGNED NOT NULL,
    "semestre_id" INT UNSIGNED NOT NULL,
    "credits" REAL NOT NULL DEFAULT 0,
    "obligatoire" INTEGER DEFAULT 1,
    "active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("filiere_id") REFERENCES "filieres"("id") ON DELETE CASCADE,
    FOREIGN KEY ("semestre_id") REFERENCES "semestres"("id") ON DELETE CASCADE
)   ;

-- Table des EC (Éléments Constitutifs)
CREATE TABLE IF NOT EXISTS "ecs" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(20) NOT NULL,
    "nom" VARCHAR(200) NOT NULL,
    "ue_id" INT UNSIGNED NOT NULL,
    "coefficient" REAL NOT NULL DEFAULT 1,
    "coefficient_cc" REAL DEFAULT 0.20,
    "coefficient_tp" REAL DEFAULT 0.20,
    "coefficient_examen" REAL DEFAULT 0.60,
    "type" VARCHAR(50) DEFAULT 'mixed',
    "vh_cours" INT DEFAULT 0,
    "vh_td" INT DEFAULT 0,
    "vh_tp" INT DEFAULT 0,
    "vh_total" INT GENERATED ALWAYS AS (IFNULL("vh_cours",0) + IFNULL("vh_td",0) + IFNULL("vh_tp",0)) STORED,
    "active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("ue_id") REFERENCES "ues"("id") ON DELETE CASCADE
)   ;

-- Table des enseignants
CREATE TABLE IF NOT EXISTS "utilisateurs" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "matricule" VARCHAR(20) UNIQUE,
    "nom" VARCHAR(100) NOT NULL,
    "prenom" VARCHAR(100) NOT NULL,
    "sexe" VARCHAR(50) NOT NULL,
    "date_naissance" DATE,
    "lieu_naissance" VARCHAR(150),
    "nationalite" VARCHAR(50) DEFAULT 'Gabonaise',
    "telephone" VARCHAR(20),
    "email" VARCHAR(150),
    "adresse" TEXT,
    "photo_path" VARCHAR(255),
    "grade" VARCHAR(50),
    "specialite" VARCHAR(150),
    "diplome" VARCHAR(255),
    "institut_id" INT UNSIGNED,
    "actif" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("institut_id") REFERENCES "instituts"("id") ON DELETE SET NULL
)   ;

-- Table d'assignation des enseignants aux EC
CREATE TABLE IF NOT EXISTS "enseigner" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "utilisateur_id" INT UNSIGNED NOT NULL,
    "ec_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "type_heures" VARCHAR(50) DEFAULT 'CM',
    "volume_horaire" INT DEFAULT 0,
    FOREIGN KEY ("utilisateur_id") REFERENCES "utilisateurs"("id") ON DELETE CASCADE,
    FOREIGN KEY ("ec_id") REFERENCES "ecs"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE
)   ;

-- Table des notes
CREATE TABLE IF NOT EXISTS "notes" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "ec_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "cc" REAL,
    "tp" REAL,
    "examen" REAL,
    "moyenne_ec" REAL,
    "observation" VARCHAR(255),
    "date_saisie" DATETIME DEFAULT CURRENT_TIMESTAMP,
    "saisi_par" INT UNSIGNED,
    "valide" INTEGER DEFAULT 0,
    "date_validation" DATETIME,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("ec_id") REFERENCES "ecs"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE,
    FOREIGN KEY ("saisi_par") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des moyennes calculées par semestre
CREATE TABLE IF NOT EXISTS "moyennes_semestrielles" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "semestre_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "moyenne_semestre" REAL,
    "credits_obtenus" INT DEFAULT 0,
    "total_credits" INT DEFAULT 0,
    "validation" VARCHAR(50) DEFAULT 'non_valide',
    "mention_semestre" VARCHAR(50),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("semestre_id") REFERENCES "semestres"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE
)   ;

-- Table des résultats annuels (moyenne DUT)
CREATE TABLE IF NOT EXISTS "resultats_annuels" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "moyenne_annuelle" REAL,
    "credits_obtenus" INT DEFAULT 0,
    "total_credits" INT DEFAULT 0,
    "decision" VARCHAR(50) NOT NULL,
    "mention" VARCHAR(50),
    "rang" INT,
    "pourcentage_reussite" REAL,
    "observation" TEXT,
    "date_jury" DATE,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE
)   ;

-- Table des incidents disciplinaires
CREATE TABLE IF NOT EXISTS "incidents" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "type" VARCHAR(50) NOT NULL,
    "description" TEXT NOT NULL,
    "gravite" VARCHAR(50) NOT NULL DEFAULT 'mineur',
    "date_incident" DATE NOT NULL,
    "lieu" VARCHAR(150),
    "temoin" VARCHAR(255),
    "utilisateur_id" INT UNSIGNED NOT NULL,
    "mesures" TEXT,
    "sanction" VARCHAR(255),
    "date_mesures" DATE,
    "statut" VARCHAR(50) DEFAULT 'en_cours',
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("utilisateur_id") REFERENCES "users"("id") ON DELETE RESTRICT
)   ;

-- Table des absences
CREATE TABLE IF NOT EXISTS "absences" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "ec_id" INT UNSIGNED,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "date_absence" DATE NOT NULL,
    "nombre_heures" INT DEFAULT 2,
    "justifiee" INTEGER DEFAULT 0,
    "motif" VARCHAR(255),
    "document_justificatif" VARCHAR(255),
    "saisi_par" INT UNSIGNED,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("ec_id") REFERENCES "ecs"("id") ON DELETE SET NULL,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE,
    FOREIGN KEY ("saisi_par") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des orientations et transferts
CREATE TABLE IF NOT EXISTS "orientations" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "filiere_origine_id" INT UNSIGNED,
    "filiere_cible_id" INT UNSIGNED,
    "semestre_origine" VARCHAR(5),
    "semestre_cible" VARCHAR(5),
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "type" VARCHAR(50) NOT NULL,
    "decision" VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    "mention" VARCHAR(50),
    "rang" INT,
    "avis_enseignant" TEXT,
    "avis_conseil" TEXT,
    "date_orientation" DATE,
    "date_decision" DATE,
    "utilisateur_id" INT UNSIGNED,
    "observation" TEXT,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("filiere_origine_id") REFERENCES "filieres"("id") ON DELETE SET NULL,
    FOREIGN KEY ("filiere_cible_id") REFERENCES "filieres"("id") ON DELETE SET NULL,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE,
    FOREIGN KEY ("utilisateur_id") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des PV de jury
CREATE TABLE IF NOT EXISTS "pvs_jury" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "semestre" VARCHAR(5) NOT NULL,
    "filiere_id" INT UNSIGNED,
    "date_jury" DATE NOT NULL,
    "lieu" VARCHAR(150),
    "president" VARCHAR(150),
    "secretaire" VARCHAR(150),
    "membres" TEXT,
    "observations" TEXT,
    "file_path" VARCHAR(255),
    "created_by" INT UNSIGNED,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE,
    FOREIGN KEY ("filiere_id") REFERENCES "filieres"("id") ON DELETE SET NULL,
    FOREIGN KEY ("created_by") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des bulletins
CREATE TABLE IF NOT EXISTS "bulletins" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "semestre" VARCHAR(5) NOT NULL,
    "moyenne_generale" REAL,
    "credits_obtenus" INT DEFAULT 0,
    "mention" VARCHAR(50),
    "rang" INT,
    "appreciation" TEXT,
    "file_path" VARCHAR(255),
    "genere_le" DATETIME,
    "signe_par" INT UNSIGNED,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE,
    FOREIGN KEY ("signe_par") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des paramètres système
CREATE TABLE IF NOT EXISTS "parametres" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "cle" VARCHAR(100) NOT NULL UNIQUE,
    "valeur" TEXT,
    "description" VARCHAR(255),
    "categorie" VARCHAR(50) DEFAULT 'general',
    "updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
)   ;

-- Table du journal d'activité
CREATE TABLE IF NOT EXISTS "journal_activite" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id" INT UNSIGNED,
    "action" VARCHAR(100) NOT NULL,
    "table_concernee" VARCHAR(100),
    "id_concerne" INT UNSIGNED,
    "details" TEXT,
    "ip_address" VARCHAR(45),
    "user_agent" VARCHAR(255),
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des notifications
CREATE TABLE IF NOT EXISTS "notifications" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id" INT UNSIGNED,
    "type" VARCHAR(50) NOT NULL,
    "titre" VARCHAR(200) NOT NULL,
    "message" TEXT,
    "lien" VARCHAR(255),
    "lu" INTEGER DEFAULT 0,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("user_id") REFERENCES "users"("id") ON DELETE CASCADE
)   ;

-- Table des salles
CREATE TABLE IF NOT EXISTS "salles" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "code" VARCHAR(20) NOT NULL UNIQUE,
    "nom" VARCHAR(200) NOT NULL,
    "capacite" INT DEFAULT 30,
    "batiment" VARCHAR(100),
    "etage" INT DEFAULT 0,
    "active" INTEGER DEFAULT 1,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)   ;

-- Table des cours/emploi du temps
CREATE TABLE IF NOT EXISTS "cours" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "ec_id" INT UNSIGNED NOT NULL,
    "enseignant_id" INT UNSIGNED,
    "salle" VARCHAR(20),
    "jour_semaine" INT NOT NULL DEFAULT 1,
    "heure_debut" TIME NOT NULL,
    "heure_fin" TIME NOT NULL,
    "type_seance" VARCHAR(10) DEFAULT 'CM',
    "groupe" VARCHAR(50),
    "semestre" VARCHAR(5) DEFAULT 'S1',
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("ec_id") REFERENCES "ecs"("id") ON DELETE CASCADE,
    FOREIGN KEY ("enseignant_id") REFERENCES "utilisateurs"("id") ON DELETE SET NULL,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE
)   ;

-- Table des messages internes
CREATE TABLE IF NOT EXISTS "messages" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "sender_id" INT UNSIGNED NOT NULL,
    "recipient_id" INT UNSIGNED,
    "subject" VARCHAR(255) NOT NULL,
    "body" TEXT,
    "parent_id" INT UNSIGNED,
    "read_at" DATETIME,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("sender_id") REFERENCES "users"("id") ON DELETE CASCADE,
    FOREIGN KEY ("recipient_id") REFERENCES "users"("id") ON DELETE SET NULL
)   ;

-- Table des frais de scolarité
CREATE TABLE IF NOT EXISTS "frais_scolarite" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "etudiant_id" INT UNSIGNED NOT NULL,
    "annee_academique_id" INT UNSIGNED NOT NULL,
    "montant_total" REAL NOT NULL DEFAULT 0,
    "montant_paye" REAL DEFAULT 0,
    "statut" VARCHAR(20) DEFAULT 'impaye',
    "echeance" DATE,
    "created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY ("etudiant_id") REFERENCES "etudiants"("id") ON DELETE CASCADE,
    FOREIGN KEY ("annee_academique_id") REFERENCES "annees_academiques"("id") ON DELETE CASCADE
)   ;

;