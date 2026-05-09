-- CUK-Admin: Base de données SQLite
-- Centre Universitaire de Koulamoutou - Cycle DUT

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'secretaire',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    telephone VARCHAR(20),
    actif INTEGER DEFAULT 1,
    derniere_connexion DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des années académiques
CREATE TABLE IF NOT EXISTS annees_academiques (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    annee VARCHAR(20) NOT NULL,
    debut DATE NOT NULL,
    fin DATE NOT NULL,
    courante INTEGER DEFAULT 0,
    inscriptions_ouvertes INTEGER DEFAULT 0,
    notes_ouvertes INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des Instituts
CREATE TABLE IF NOT EXISTS instituts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    sigle VARCHAR(20) NOT NULL,
    responsable_id INTEGER,
    actif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des Filières DUT
CREATE TABLE IF NOT EXISTS filieres (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(200) NOT NULL,
    nom_complet VARCHAR(255),
    description TEXT,
    institut_id INTEGER NOT NULL,
    responsable_id INTEGER,
    duree_ans INTEGER DEFAULT 2,
    credits_total INTEGER DEFAULT 120,
    actif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institut_id) REFERENCES instituts(id)
);

-- Table des semestres
CREATE TABLE IF NOT EXISTS semestres (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(10) NOT NULL UNIQUE,
    nom VARCHAR(50) NOT NULL,
    numero INTEGER NOT NULL,
    filiere_id INTEGER NOT NULL,
    credits INTEGER DEFAULT 30,
    actif INTEGER DEFAULT 1,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id)
);

-- Table des étudiants
CREATE TABLE IF NOT EXISTS etudiants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero VARCHAR(20) NOT NULL UNIQUE,
    matricule VARCHAR(20) UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe VARCHAR(1) NOT NULL,
    date_naissance DATE NOT NULL,
    lieu_naissance VARCHAR(150),
    nationalite VARCHAR(50) DEFAULT 'Gabonaise',
    telephone VARCHAR(20),
    email VARCHAR(150),
    adresse TEXT,
    photo_path VARCHAR(255),
    cni_path VARCHAR(255),
    bac_path VARCHAR(255),
    diplome_path VARCHAR(255),
    filiere_id INTEGER NOT NULL,
    semestre VARCHAR(5) NOT NULL DEFAULT 'S1',
    annee_academique_id INTEGER NOT NULL,
    date_inscription DATE NOT NULL,
    statut VARCHAR(20) DEFAULT 'actif',
    boursier INTEGER DEFAULT 0,
    type_bourse VARCHAR(50),
    password_hash VARCHAR(255),
    observation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id)
);

-- Table des UE
CREATE TABLE IF NOT EXISTS ues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(20) NOT NULL,
    nom VARCHAR(200) NOT NULL,
    description TEXT,
    filiere_id INTEGER NOT NULL,
    semestre_id INTEGER NOT NULL,
    credits REAL NOT NULL DEFAULT 0,
    obligatoire INTEGER DEFAULT 1,
    actif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filiere_id) REFERENCES filieres(id),
    FOREIGN KEY (semestre_id) REFERENCES semestres(id),
    UNIQUE(code, filiere_id, semestre_id)
);

-- Table des EC
CREATE TABLE IF NOT EXISTS ecs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(20) NOT NULL,
    nom VARCHAR(200) NOT NULL,
    ue_id INTEGER NOT NULL,
    coefficient REAL NOT NULL DEFAULT 1,
    coefficient_cc REAL DEFAULT 0.20,
    coefficient_tp REAL DEFAULT 0.20,
    coefficient_examen REAL DEFAULT 0.60,
    type VARCHAR(20) DEFAULT 'mixed',
    vh_cours INTEGER DEFAULT 0,
    vh_td INTEGER DEFAULT 0,
    vh_tp INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ue_id) REFERENCES ues(id),
    UNIQUE(code, ue_id)
);

-- Table des enseignants
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    matricule VARCHAR(20) UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    sexe VARCHAR(1) NOT NULL,
    date_naissance DATE,
    lieu_naissance VARCHAR(150),
    nationalite VARCHAR(50) DEFAULT 'Gabonaise',
    telephone VARCHAR(20),
    email VARCHAR(150),
    adresse TEXT,
    photo_path VARCHAR(255),
    grade VARCHAR(50),
    specialite VARCHAR(150),
    diplome VARCHAR(255),
    institut_id INTEGER,
    actif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institut_id) REFERENCES instituts(id)
);

-- Table des notes
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    ec_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    cc REAL,
    tp REAL,
    examen REAL,
    moyenne_ec REAL,
    observation VARCHAR(255),
    date_saisie DATETIME DEFAULT CURRENT_TIMESTAMP,
    saisi_par INTEGER,
    valide INTEGER DEFAULT 0,
    date_validation DATETIME,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (ec_id) REFERENCES ecs(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    UNIQUE(etudiant_id, ec_id, annee_academique_id)
);

-- Table des moyennes semestrielles
CREATE TABLE IF NOT EXISTS moyennes_semestrielles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    semestre_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    moyenne_semestre REAL,
    credits_obtenus INTEGER DEFAULT 0,
    total_credits INTEGER DEFAULT 0,
    validation VARCHAR(20) DEFAULT 'non_valide',
    mention_semestre VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (semestre_id) REFERENCES semestres(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    UNIQUE(etudiant_id, semestre_id, annee_academique_id)
);

-- Table des résultats annuels
CREATE TABLE IF NOT EXISTS resultats_annuels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    moyenne_annuelle REAL,
    credits_obtenus INTEGER DEFAULT 0,
    total_credits INTEGER DEFAULT 0,
    decision VARCHAR(20) NOT NULL,
    mention VARCHAR(50),
    rang INTEGER,
    pourcentage_reussite REAL,
    observation TEXT,
    date_jury DATE,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    UNIQUE(etudiant_id, annee_academique_id)
);

-- Table des incidents
CREATE TABLE IF NOT EXISTS incidents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    gravite VARCHAR(20) NOT NULL DEFAULT 'mineur',
    date_incident DATE NOT NULL,
    lieu VARCHAR(150),
    temoin VARCHAR(255),
    utilisateur_id INTEGER NOT NULL,
    mesures TEXT,
    sanction VARCHAR(255),
    date_mesures DATE,
    statut VARCHAR(20) DEFAULT 'en_cours',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (utilisateur_id) REFERENCES users(id)
);

-- Table des absences
CREATE TABLE IF NOT EXISTS absences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    ec_id INTEGER,
    annee_academique_id INTEGER NOT NULL,
    date_absence DATE NOT NULL,
    nombre_heures INTEGER DEFAULT 2,
    justifiee INTEGER DEFAULT 0,
    motif VARCHAR(255),
    document_justificatif VARCHAR(255),
    saisi_par INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (ec_id) REFERENCES ecs(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id)
);

-- Table des orientations
CREATE TABLE IF NOT EXISTS orientations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    filiere_origine_id INTEGER,
    filiere_cible_id INTEGER,
    semestre_origine VARCHAR(5),
    semestre_cible VARCHAR(5),
    annee_academique_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    decision VARCHAR(20) NOT NULL DEFAULT 'en_attente',
    mention VARCHAR(50),
    rang INTEGER,
    avis_enseignant TEXT,
    avis_conseil TEXT,
    date_orientation DATE,
    date_decision DATE,
    utilisateur_id INTEGER,
    observation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id)
);

-- Table des PV jury
CREATE TABLE IF NOT EXISTS pvs_jury (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    annee_academique_id INTEGER NOT NULL,
    semestre VARCHAR(5) NOT NULL,
    filiere_id INTEGER,
    date_jury DATE NOT NULL,
    lieu VARCHAR(150),
    president VARCHAR(150),
    secretaire VARCHAR(150),
    membres TEXT,
    observations TEXT,
    file_path VARCHAR(255),
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Table des bulletins
CREATE TABLE IF NOT EXISTS bulletins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    semestre VARCHAR(5) NOT NULL,
    moyenne_generale REAL,
    credits_obtenus INTEGER DEFAULT 0,
    mention VARCHAR(50),
    rang INTEGER,
    appreciation TEXT,
    file_path VARCHAR(255),
    genere_le DATETIME,
    signe_par INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    UNIQUE(etudiant_id, annee_academique_id, semestre)
);

-- Table des paramètres
CREATE TABLE IF NOT EXISTS parametres (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT,
    description VARCHAR(255),
    categorie VARCHAR(50) DEFAULT 'general',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table du journal
CREATE TABLE IF NOT EXISTS journal_activite (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    table_concernee VARCHAR(100),
    id_concerne INTEGER,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    type VARCHAR(50) NOT NULL,
    titre VARCHAR(200) NOT NULL,
    message TEXT,
    lien VARCHAR(255),
    lu INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table pour enseigner
CREATE TABLE IF NOT EXISTS enseigner (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilisateur_id INTEGER NOT NULL,
    ec_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    type_heures VARCHAR(10) DEFAULT 'CM',
    volume_horaire INTEGER DEFAULT 0,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (ec_id) REFERENCES ecs(id),
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id),
    UNIQUE(utilisateur_id, ec_id, annee_academique_id)
);