-- Données initiales CUK-Admin SQLite
-- Centre Universitaire de Koulamoutou

-- Utilisateurs (mots de passe uniques, changement forcé à la 1ère connexion)
INSERT INTO users (username, password_hash, role, nom, prenom, email, telephone, actif) VALUES
('admin', '$2y$10$0OFdcMfCWBHlDqXb3s2RMuw24n58jOd2w.UFsuEbc4xXrKXCAksfm', 'root', 'Administrateur', 'Système', 'admin@cuk-gabon.ga', '+241 01 23 45 67', 1),
('secretaire', '$2y$10$.MO8mErpsPHBrP2ldKv2Geg2lBWi8t43dtO9VrrvGfX/p1kmzmyuK', 'secretaire', 'Moubembe', 'Jeanne', 'secretaire@cuk-gabon.ga', '+241 07 11 22 33', 1),
('prof_ngouala', '$2y$10$GFn1nv2jGHu52v9rnGjDfup10Fa3aOeJi8YOONmbmIo7Z0QrdA5d6', 'professeur', 'Ngouala', 'Jean-Pierre', 'ngouala@cuk-gabon.ga', '+241 06 44 55 66', 1),
('prof_mouyama', '$2y$10$OK.34lxwHbGGNgeVY6wO2uC8lkv241MBNvjjobxpxcKVo0ouNAeJC', 'professeur', 'Mouyama', 'Marie', 'mouyama@cuk-gabon.ga', '+241 07 77 88 99', 1);

-- Années académiques
INSERT INTO annees_academiques (annee, debut, fin, courante, inscriptions_ouvertes, notes_ouvertes, active) VALUES
('2024-2025', '2024-10-01', '2025-07-15', 0, 0, 0, 1),
('2025-2026', '2025-10-01', '2026-07-15', 1, 1, 1, 1),
('2026-2027', '2026-10-01', '2027-07-15', 0, 0, 0, 1);

-- Instituts du CUK
INSERT INTO instituts (code, nom, sigle, description, actif) VALUES
('ISTPK', 'Institut des Sciences et Technique Paul Kouya', 'ISTPK', 'LInstitut des Sciences et Technique Paul Kouya forme des techniciens superieurs.', 1),
('ISTS', 'Institut des Sciences Technologiques de la Santé', 'ISTS', 'LInstitut des Sciences Technologiques de la Sante prepare aux metiers de la sante.', 1);

-- Filières DUT
INSERT INTO filieres (code, nom, nom_complet, institut_id, description, duree_ans, credits_total) VALUES
('ISTPK-AEC', 'Architecture et Eco-construction', 'DUT en Architecture et Eco-construction', 1, 'Formation en conception architecturale et construction durable', 2, 120),
('ISTPK-CI', 'Chimie Industrielle', 'DUT en Chimie Industrielle', 1, 'Formation en chimie industrielle et procedes de fabrication', 2, 120),
('ISTPK-GTER', 'Genie Thermique et Energies Renouvelables', 'DUT en Genie Thermique et Energies Renouvelables', 1, 'Formation en energie et environnement', 2, 120),
('ISTPK-IC', 'Informatique et Communication', 'DUT en Informatique et Communication', 1, 'Formation en developpement logiciel et reseaux', 2, 120),
('ISTPK-PM', 'Productique Mecanique', 'DUT en Productique Mecanique', 1, 'Formation en fabrication mecanique et productique', 2, 120),
('ISTS-ABB', 'Analyses Biologiques et Biochimiques', 'DUT en Analyses Biologiques et Biochimiques', 2, 'Formation en analyses biomedicales et biotechnologies', 2, 120),
('ISTS-MEB', 'Maintenance des Equipements Biomedicaux', 'DUT en Maintenance des Equipements Biomedicaux', 2, 'Formation en maintenance equipements de sante', 2, 120);

-- Semestres pour chaque filière
INSERT INTO semestres (code, nom, numero, filiere_id, credits) VALUES
('ISTPK-AEC-S1', 'Semestre 1', 1, 1, 30), ('ISTPK-AEC-S2', 'Semestre 2', 2, 1, 30),
('ISTPK-AEC-S3', 'Semestre 3', 3, 1, 30), ('ISTPK-AEC-S4', 'Semestre 4', 4, 1, 30),
('ISTPK-CI-S1', 'Semestre 1', 1, 2, 30), ('ISTPK-CI-S2', 'Semestre 2', 2, 2, 30),
('ISTPK-CI-S3', 'Semestre 3', 3, 2, 30), ('ISTPK-CI-S4', 'Semestre 4', 4, 2, 30),
('ISTPK-GTER-S1', 'Semestre 1', 1, 3, 30), ('ISTPK-GTER-S2', 'Semestre 2', 2, 3, 30),
('ISTPK-GTER-S3', 'Semestre 3', 3, 3, 30), ('ISTPK-GTER-S4', 'Semestre 4', 4, 3, 30),
('ISTPK-IC-S1', 'Semestre 1', 1, 4, 30), ('ISTPK-IC-S2', 'Semestre 2', 2, 4, 30),
('ISTPK-IC-S3', 'Semestre 3', 3, 4, 30), ('ISTPK-IC-S4', 'Semestre 4', 4, 4, 30),
('ISTPK-PM-S1', 'Semestre 1', 1, 5, 30), ('ISTPK-PM-S2', 'Semestre 2', 2, 5, 30),
('ISTPK-PM-S3', 'Semestre 3', 3, 5, 30), ('ISTPK-PM-S4', 'Semestre 4', 4, 5, 30),
('ISTS-ABB-S1', 'Semestre 1', 1, 6, 30), ('ISTS-ABB-S2', 'Semestre 2', 2, 6, 30),
('ISTS-ABB-S3', 'Semestre 3', 3, 6, 30), ('ISTS-ABB-S4', 'Semestre 4', 4, 6, 30),
('ISTS-MEB-S1', 'Semestre 1', 1, 7, 30), ('ISTS-MEB-S2', 'Semestre 2', 2, 7, 30),
('ISTS-MEB-S3', 'Semestre 3', 3, 7, 30), ('ISTS-MEB-S4', 'Semestre 4', 4, 7, 30);

-- UE pour Informatique et Communication S1
INSERT INTO ues (code, nom, filiere_id, semestre_id, credits, obligatoire) VALUES
('ISTPK-IC-S1-UE1', 'Outils Mathematiques', 4, 13, 6, 1),
('ISTPK-IC-S1-UE2', 'Programmation C', 4, 13, 6, 1),
('ISTPK-IC-S1-UE3', 'Algorithmique', 4, 13, 4, 1),
('ISTPK-IC-S1-UE4', 'Systemes dExploitation', 4, 13, 4, 1),
('ISTPK-IC-S1-UE5', 'Anglais Technique', 4, 13, 2, 1),
('ISTPK-IC-S1-UE6', 'Communication', 4, 13, 2, 1),
('ISTPK-IC-S1-UE7', 'Projet Tutore', 4, 13, 6, 1);

-- UE pour IC S2
INSERT INTO ues (code, nom, filiere_id, semestre_id, credits, obligatoire) VALUES
('ISTPK-IC-S2-UE1', 'Mathematiques Appliquees', 4, 14, 6, 1),
('ISTPK-IC-S2-UE2', 'Programmation Orientee Objet', 4, 14, 6, 1),
('ISTPK-IC-S2-UE3', 'Bases de Donnees', 4, 14, 5, 1),
('ISTPK-IC-S2-UE4', 'Reseaux Informatiques', 4, 14, 4, 1),
('ISTPK-IC-S2-UE5', 'Anglais Technique 2', 4, 14, 2, 1),
('ISTPK-IC-S2-UE6', 'Communication 2', 4, 14, 2, 1),
('ISTPK-IC-S2-UE7', 'Stage de Decouverte', 4, 14, 5, 1);

-- UE pour Analyses Biologiques S1
INSERT INTO ues (code, nom, filiere_id, semestre_id, credits, obligatoire) VALUES
('ISTS-ABB-S1-UE1', 'Biologie Cellulaire', 6, 21, 6, 1),
('ISTS-ABB-S1-UE2', 'Biochimie Generale', 6, 21, 6, 1),
('ISTS-ABB-S1-UE3', 'Chimie Generale', 6, 21, 4, 1),
('ISTS-ABB-S1-UE4', 'Physiologie', 6, 21, 4, 1),
('ISTS-ABB-S1-UE5', 'Anglais Technique', 6, 21, 2, 1),
('ISTS-ABB-S1-UE6', 'Communication', 6, 21, 2, 1),
('ISTS-ABB-S1-UE7', 'Projet Tutore', 6, 21, 6, 1);

-- UE pour Analyses Biologiques S2
INSERT INTO ues (code, nom, filiere_id, semestre_id, credits, obligatoire) VALUES
('ISTS-ABB-S2-UE1', 'Microbiologie', 6, 22, 6, 1),
('ISTS-ABB-S2-UE2', 'Biochimie Analytique', 6, 22, 6, 1),
('ISTS-ABB-S2-UE3', 'Hematologie', 6, 22, 5, 1),
('ISTS-ABB-S2-UE4', 'Immunologie', 6, 22, 4, 1),
('ISTS-ABB-S2-UE5', 'Anglais Technique 2', 6, 22, 2, 1),
('ISTS-ABB-S2-UE6', 'Communication 2', 6, 22, 2, 1),
('ISTS-ABB-S2-UE7', 'Stage de Decouverte', 6, 22, 5, 1);

-- EC pour UE Programmation C
INSERT INTO ecs (code, nom, ue_id, coefficient, coefficient_cc, coefficient_tp, coefficient_examen, type) VALUES
('IC-C-PROG', 'Programmation C', 1, 2, 0.30, 0.30, 0.40, 'pratique'),
('IC-C-STRUCT', 'Structures de Donnees', 1, 2, 0.30, 0.30, 0.40, 'pratique'),
('IC-C-TP', 'Travaux Pratiques C', 1, 1, 0.00, 1.00, 0.00, 'pratique');

-- EC pour UE Algorithmique
INSERT INTO ecs (code, nom, ue_id, coefficient, coefficient_cc, coefficient_tp, coefficient_examen, type) VALUES
('IC-ALGO-BASE', 'Algorithmique de Base', 2, 2, 0.40, 0.20, 0.40, 'theorique'),
('IC-ALGO-AV', 'Algorithmique Avancee', 2, 2, 0.40, 0.20, 0.40, 'theorique');

-- EC pour Outils Mathematiques
INSERT INTO ecs (code, nom, ue_id, coefficient, coefficient_cc, coefficient_tp, coefficient_examen, type) VALUES
('IC-MATH-ALG', 'Algebre Lineaire', 0, 3, 0.30, 0.10, 0.60, 'theorique'),
('IC-MATH-ANA', 'Analyse', 0, 3, 0.30, 0.10, 0.60, 'theorique');

-- EC pour Biochimie
INSERT INTO ecs (code, nom, ue_id, coefficient, coefficient_cc, coefficient_tp, coefficient_examen, type) VALUES
('ABB-BIOCHIM-G', 'Biochimie Generale', 9, 3, 0.30, 0.20, 0.50, 'mixed'),
('ABB-ENZYM', 'Enzymologie', 9, 3, 0.30, 0.20, 0.50, 'mixed');

-- EC pour Biologie Cellulaire
INSERT INTO ecs (code, nom, ue_id, coefficient, coefficient_cc, coefficient_tp, coefficient_examen, type) VALUES
('ABB-BIO-CELL', 'Biologie Cellulaire', 8, 3, 0.30, 0.20, 0.50, 'mixed'),
('ABB-GENET', 'Genetique', 8, 3, 0.30, 0.20, 0.50, 'mixed');

-- Paramètres
INSERT INTO parametres (cle, valeur, description, categorie) VALUES
('nom_etablissement', 'Centre Universitaire de Koulamoutou', 'Nom officiel', 'general'),
('sigle', 'CUK', 'Sigle', 'general'),
('adresse', 'Koulamoutou, Province de Ogooue-Lolo, Gabon', 'Adresse', 'general'),
('telephone', '+241 01 XX XX XX', 'Telephone', 'general'),
('email', 'contact@cuk-gabon.ga', 'Email', 'general'),
('directeur', 'Dr. Bernard IVOT', 'Directeur', 'general'),
('seuil_reussite', '10', 'Seuil de validation', 'notation'),
('seuil_bourse', '12', 'Seuil pour bourse', 'notation'),
('seuil_honneur', '14', 'Seuil mention honneur', 'notation'),
('credits_passage', '60', 'Credits minimum passage', 'notation'),
('credits_dut', '120', 'Credits total DUT', 'notation');

-- Etudiants
INSERT INTO etudiants (numero, matricule, nom, prenom, sexe, date_naissance, lieu_naissance, nationalite, telephone, email, adresse, filiere_id, semestre, annee_academique_id, date_inscription, statut, boursier, observation) VALUES
('ETU-2025-001', 'MAT-2025-001', 'Mekui', 'Brice', 'M', '2002-05-12', 'Koulamoutou', 'Gabonaise', '+241 07 11 22 33', 'mekui.brice@etu.cuk-gabon.ga', 'Quartier Centre, Koulamoutou', 4, 'S1', 2, '2025-10-05', 'actif', 1, 'Boursier'),
('ETU-2025-002', 'MAT-2025-002', 'Obame', 'Christelle', 'F', '2001-08-25', 'Lastourville', 'Gabonaise', '+241 07 44 55 66', 'obame.christelle@etu.cuk-gabon.ga', 'Quartier Plateau, Koulamoutou', 4, 'S1', 2, '2025-10-06', 'actif', 0, NULL),
('ETU-2025-003', 'MAT-2025-003', 'Moussavou', 'Armel', 'M', '2003-02-14', 'Moanda', 'Gabonaise', '+241 06 77 88 99', 'moussavou.armel@etu.cuk-gabon.ga', 'Quartier Bahaut, Koulamoutou', 6, 'S1', 2, '2025-10-07', 'actif', 1, 'Boursier'),
('ETU-2025-004', 'MAT-2025-004', 'Nguema', 'Sonia', 'F', '2002-11-03', 'Libreville', 'Gabonaise', '+241 07 22 33 44', 'nguema.sonia@etu.cuk-gabon.ga', 'Quartier Kennedy, Koulamoutou', 4, 'S1', 2, '2025-10-08', 'actif', 0, 'Transfert'),
('ETU-2025-005', 'MAT-2025-005', 'Mabika', 'Didier', 'M', '2000-06-18', 'Koulamoutou', 'Gabonaise', '+241 06 55 66 77', 'mabika.didier@etu.cuk-gabon.ga', 'Quartier Commerce, Koulamoutou', 6, 'S1', 2, '2025-10-05', 'actif', 0, NULL),
('ETU-2025-006', 'MAT-2025-006', 'Adaghe', 'Esther', 'F', '2002-03-22', 'Koulamoutou', 'Gabonaise', '+241 07 66 77 88', 'adaghe.esther@etu.cuk-gabon.ga', 'Quartier Centre, Koulamoutou', 1, 'S1', 2, '2025-10-09', 'actif', 0, NULL),
('ETU-2025-007', 'MAT-2025-007', 'Mve', 'Jean-Marc', 'M', '2001-09-15', 'Moanda', 'Gabonaise', '+241 06 88 99 00', 'mve.jeanmarc@etu.cuk-gabon.ga', 'Quartier Bahaut, Koulamoutou', 3, 'S1', 2, '2025-10-10', 'actif', 1, 'Boursier');

-- Notes
INSERT INTO notes (etudiant_id, ec_id, annee_academique_id, cc, tp, examen, saisi_par, valide) VALUES
(1, 0, 2, 14.00, 14.00, 15.50, 2, 1),
(1, 1, 2, 12.00, NULL, 13.00, 2, 1),
(1, 2, 2, 15.00, 14.00, NULL, 2, 1),
(1, 3, 2, 10.00, NULL, 11.00, 2, 1),
(1, 4, 2, 14.00, NULL, 15.00, 2, 1),
(1, 5, 2, 13.00, NULL, 14.00, 2, 1),
(3, 7, 2, 14.00, 13.00, 15.00, 2, 1),
(3, 8, 2, 12.00, NULL, 13.50, 2, 1),
(3, 9, 2, 15.00, 14.00, 16.00, 2, 1),
(3, 10, 2, 13.00, NULL, 14.00, 2, 1);

-- Incident
INSERT INTO incidents (etudiant_id, type, description, gravite, date_incident, lieu, utilisateur_id, mesures, statut) VALUES
(2, 'retard', 'Retard de 15 minutes en cours de Programmation C', 'mineur', '2025-11-20', 'Salle B201', 2, 'Avertissement oral', 'cloture');

-- Absences
INSERT INTO absences (etudiant_id, ec_id, annee_academique_id, date_absence, nombre_heures, justifiee, motif, saisi_par) VALUES
(2, 0, 2, '2025-11-18', 2, 1, 'Rendez-vous medical', 2),
(3, 7, 2, '2025-11-22', 3, 0, 'Non justifie', 2);

-- Notifications
INSERT INTO notifications (user_id, type, titre, message) VALUES
(1, 'info', 'Bienvenue sur CUK-Admin', 'Bienvenue sur le systeme de gestion du CUK.'),
(2, 'info', 'Inscriptions ouvertes', 'Les inscriptions 2025-2026 sont ouvertes.');