CREATE TABLE IF NOT EXISTS cours (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ec_id INTEGER NOT NULL,
    enseignant_id INTEGER,
    salle VARCHAR(50),
    jour_semaine INTEGER NOT NULL CHECK(jour_semaine BETWEEN 1 AND 7),
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    type_seance VARCHAR(20) DEFAULT 'CM',
    groupe VARCHAR(50),
    semestre VARCHAR(5) NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ec_id) REFERENCES ecs(id) ON DELETE CASCADE,
    FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS salles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    capacite INTEGER DEFAULT 30,
    batiment VARCHAR(50),
    equipements TEXT
);
