CREATE TABLE IF NOT EXISTS frais_scolarite (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    montant_total REAL NOT NULL,
    montant_paye REAL DEFAULT 0,
    echeance1 REAL,
    echeance1_date DATE,
    echeance2 REAL,
    echeance2_date DATE,
    statut VARCHAR(20) DEFAULT 'impaye',
    observation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS paiements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    frais_id INTEGER NOT NULL,
    montant REAL NOT NULL,
    date_paiement DATE NOT NULL,
    mode_paiement VARCHAR(50) DEFAULT 'especes',
    reference VARCHAR(100),
    reçu_path VARCHAR(255),
    saisi_par INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (frais_id) REFERENCES frais_scolarite(id) ON DELETE CASCADE,
    FOREIGN KEY (saisi_par) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS bourses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    type_bourse VARCHAR(50),
    montant_annuel REAL DEFAULT 0,
    organisme VARCHAR(100),
    date_attribution DATE,
    duree_mois INTEGER DEFAULT 12,
    actif INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE
);
