CREATE TABLE IF NOT EXISTS demandes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    etudiant_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    description TEXT,
    statut VARCHAR(20) DEFAULT 'en_attente',
    reponse TEXT,
    traite_par INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    traite_le DATETIME,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (traite_par) REFERENCES users(id) ON DELETE SET NULL
);
