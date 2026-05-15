-- =====================================================
-- CUK-Admin: Données initiales
-- Centre Universitaire de Koulamoutou - Cycle DUT
-- =====================================================

SET NAMES utf8mb4;

-- Insertion des utilisateurs root et administrateurs
-- ATTENTION: Chaque compte a un mot de passe unique fort ci-dessous.
-- Le système forcera le changement de mot de passe à la première connexion.
-- Comptes de démo pour développement uniquement - NE PAS UTILISER EN PRODUCTION.
INSERT INTO `users` (`username`, `password_hash`, `role`, `nom`, `prenom`, `email`, `telephone`, `actif`) VALUES
('admin', '$2y$10$0OFdcMfCWBHlDqXb3s2RMuw24n58jOd2w.UFsuEbc4xXrKXCAksfm', 'root', 'Administrateur', 'Système', 'admin@cuk-gabon.ga', '+241 01 23 45 67', 1),
('secretaire', '$2y$10$.MO8mErpsPHBrP2ldKv2Geg2lBWi8t43dtO9VrrvGfX/p1kmzmyuK', 'secretaire', 'Moubembe', 'Jeanne', 'secretaire@cuk-gabon.ga', '+241 07 11 22 33', 1),
('prof_ngouala', '$2y$10$GFn1nv2jGHu52v9rnGjDfup10Fa3aOeJi8YOONmbmIo7Z0QrdA5d6', 'professeur', 'Ngouala', 'Jean-Pierre', 'ngouala@cuk-gabon.ga', '+241 06 44 55 66', 1),
('prof_mouyama', '$2y$10$OK.34lxwHbGGNgeVY6wO2uC8lkv241MBNvjjobxpxcKVo0ouNAeJC', 'professeur', 'Mouyama', 'Marie', 'mouyama@cuk-gabon.ga', '+241 07 77 88 99', 1);

-- Années académiques
INSERT INTO `annees_academiques` (`annee`, `debut`, `fin`, `courante`, `inscriptions_ouvertes`, `notes_ouvertes`, `active`) VALUES
('2024-2025', '2024-10-01', '2025-07-15', 0, 0, 0, 1),
('2025-2026', '2025-10-01', '2026-07-15', 1, 1, 1, 1),
('2026-2027', '2026-10-01', '2027-07-15', 0, 0, 0, 1);

-- Instituts du CUK
INSERT INTO `instituts` (`code`, `nom`, `sigle`, `description`, `actif`) VALUES
('ISTPK', 'Institut des Sciences et Technique Paul Kouya', 'ISTPK', 'L'Institut des Sciences et Technique Paul Kouya forme des techniciens supérieurs dans les domaines des sciences appliquées et de l''ingénierie. Héritier du Lycée Polytechnique Paul Kouya.', 1),
('ISTS', 'Institut des Sciences Technologiques de la Santé', 'ISTS', 'L''Institut des Sciences Technologiques de la Santé prépare aux métiers de la santé et du biomédical.', 1);

-- Filières DUT - ISTPK
INSERT INTO `filieres` (`code`, `nom`, `nom_complet`, `institut_id`, `description`, `duree_ans`, `credits_total`) VALUES
('ISTPK-AEC', 'Architecture et Éco-construction', 'DUT en Architecture et Éco-construction', 1, 'Formation en conception architecturale et construction durable', 2, 120),
('ISTPK-CI', 'Chimie Industrielle', 'DUT en Chimie Industrielle', 1, 'Formation en chimie industrielle et procédés de fabrication', 2, 120),
('ISTPK-GTER', 'Génie Thermique et Énergies Renouvelables', 'DUT en Génie Thermique et Énergies Renouvelables', 1, 'Formation en énergie et environnement', 2, 120),
('ISTPK-IC', 'Informatique et Communication', 'DUT en Informatique et Communication', 1, 'Formation en développement logiciel et réseaux', 2, 120),
('ISTPK-PM', 'Productique Mécanique', 'DUT en Productique Mécanique', 1, 'Formation en fabrication mécanique et productique', 2, 120);

-- Filières DUT - ISTS
INSERT INTO `filieres` (`code`, `nom`, `nom_complet`, `institut_id`, `description`, `duree_ans`, `credits_total`) VALUES
('ISTS-ABB', 'Analyses Biologiques et Biochimiques', 'DUT en Analyses Biologiques et Biochimiques', 2, 'Formation en analyses biomédicales et biotechnologies', 2, 120),
('ISTS-MEB', 'Maintenance des Équipements Biomédicaux', 'DUT en Maintenance des Équipements Biomédicaux', 2, 'Formation en maintenance équipements de santé', 2, 120);

-- Semestres pour chaque filière (S1, S2, S3, S4)
-- Architecture et Éco-construction
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTPK-AEC-S1', 'Semestre 1', 1, 1, 30),
('ISTPK-AEC-S2', 'Semestre 2', 2, 1, 30),
('ISTPK-AEC-S3', 'Semestre 3', 3, 1, 30),
('ISTPK-AEC-S4', 'Semestre 4', 4, 1, 30);

-- Chimie Industrielle
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTPK-CI-S1', 'Semestre 1', 1, 2, 30),
('ISTPK-CI-S2', 'Semestre 2', 2, 2, 30),
('ISTPK-CI-S3', 'Semestre 3', 3, 2, 30),
('ISTPK-CI-S4', 'Semestre 4', 4, 2, 30);

-- Génie Thermique et Énergies Renouvelables
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTPK-GTER-S1', 'Semestre 1', 1, 3, 30),
('ISTPK-GTER-S2', 'Semestre 2', 2, 3, 30),
('ISTPK-GTER-S3', 'Semestre 3', 3, 3, 30),
('ISTPK-GTER-S4', 'Semestre 4', 4, 3, 30);

-- Informatique et Communication
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTPK-IC-S1', 'Semestre 1', 1, 4, 30),
('ISTPK-IC-S2', 'Semestre 2', 2, 4, 30),
('ISTPK-IC-S3', 'Semestre 3', 3, 4, 30),
('ISTPK-IC-S4', 'Semestre 4', 4, 4, 30);

-- Productique Mécanique
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTPK-PM-S1', 'Semestre 1', 1, 5, 30),
('ISTPK-PM-S2', 'Semestre 2', 2, 5, 30),
('ISTPK-PM-S3', 'Semestre 3', 3, 5, 30),
('ISTPK-PM-S4', 'Semestre 4', 4, 5, 30);

-- Analyses Biologiques et Biochimiques
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTS-ABB-S1', 'Semestre 1', 1, 6, 30),
('ISTS-ABB-S2', 'Semestre 2', 2, 6, 30),
('ISTS-ABB-S3', 'Semestre 3', 3, 6, 30),
('ISTS-ABB-S4', 'Semestre 4', 4, 6, 30);

-- Maintenance des Équipements Biomédicaux
INSERT INTO `semestres` (`code`, `nom`, `numero`, `filiere_id`, `credits`) VALUES
('ISTS-MEB-S1', 'Semestre 1', 1, 7, 30),
('ISTS-MEB-S2', 'Semestre 2', 2, 7, 30),
('ISTS-MEB-S3', 'Semestre 3', 3, 7, 30),
('ISTS-MEB-S4', 'Semestre 4', 4, 7, 30);

-- UE pour Informatique et Communication S1
INSERT INTO `ues` (`code`, `nom`, `filiere_id`, `semestre_id`, `credits`, `obligatoire`) VALUES
-- S1
('ISTPK-IC-S1-UE1', 'Outils Mathématiques', 4, 17, 6, 1),
('ISTPK-IC-S1-UE2', 'Programmation C', 4, 17, 6, 1),
('ISTPK-IC-S1-UE3', 'Algorithmique', 4, 17, 4, 1),
('ISTPK-IC-S1-UE4', 'Systèmes d''Exploitation', 4, 17, 4, 1),
('ISTPK-IC-S1-UE5', 'Anglais Technique', 4, 17, 2, 1),
('ISTPK-IC-S1-UE6', 'Communication', 4, 17, 2, 1),
('ISTPK-IC-S1-UE7', 'Projet Tutoré', 4, 17, 6, 1);

-- UE pour Informatique et Communication S2
INSERT INTO `ues` (`code`, `nom`, `filiere_id`, `semestre_id`, `credits`, `obligatoire`) VALUES
('ISTPK-IC-S2-UE1', 'Mathématiques Appliquées', 4, 18, 6, 1),
('ISTPK-IC-S2-UE2', 'Programmation Orientée Objet', 4, 18, 6, 1),
('ISTPK-IC-S2-UE3', 'Bases de Données', 4, 18, 5, 1),
('ISTPK-IC-S2-UE4', 'Réseaux Informatiques', 4, 18, 4, 1),
('ISTPK-IC-S2-UE5', 'Anglais Technique 2', 4, 18, 2, 1),
('ISTPK-IC-S2-UE6', 'Communication 2', 4, 18, 2, 1),
('ISTPK-IC-S2-UE7', 'Stage de Découverte', 4, 18, 5, 1);

-- UE pour Analyse Biologique et Biochimique S1
INSERT INTO `ues` (`code`, `nom`, `filiere_id`, `semestre_id`, `credits`, `obligatoire`) VALUES
('ISTS-ABB-S1-UE1', 'Biologie Cellulaire', 6, 25, 6, 1),
('ISTS-ABB-S1-UE2', 'Biochimie Générale', 6, 25, 6, 1),
('ISTS-ABB-S1-UE3', 'Chimie Générale', 6, 25, 4, 1),
('ISTS-ABB-S1-UE4', 'Physiologie', 6, 25, 4, 1),
('ISTS-ABB-S1-UE5', 'Anglais Technique', 6, 25, 2, 1),
('ISTS-ABB-S1-UE6', 'Communication', 6, 25, 2, 1),
('ISTS-ABB-S1-UE7', 'Projet Tutoré', 6, 25, 6, 1);

-- UE pour Analyse Biologique et Biochimique S2
INSERT INTO `ues` (`code`, `nom`, `filiere_id`, `semestre_id`, `credits`, `obligatoire`) VALUES
('ISTS-ABB-S2-UE1', 'Microbiologie', 6, 26, 6, 1),
('ISTS-ABB-S2-UE2', 'Biochimie Analytique', 6, 26, 6, 1),
('ISTS-ABB-S2-UE3', 'Hématologie', 6, 26, 5, 1),
('ISTS-ABB-S2-UE4', 'Immunologie', 6, 26, 4, 1),
('ISTS-ABB-S2-UE5', 'Anglais Technique 2', 6, 26, 2, 1),
('ISTS-ABB-S2-UE6', 'Communication 2', 6, 26, 2, 1),
('ISTS-ABB-S2-UE7', 'Stage de Découverte', 6, 26, 5, 1);

-- EC pour UE Programmation C
INSERT INTO `ecs` (`code`, `nom`, `ue_id`, `coefficient`, `coefficient_cc`, `coefficient_tp`, `coefficient_examen`, `type`) VALUES
('IC-C-PROG', 'Programmation C', 8, 2, 0.30, 0.30, 0.40, 'pratique'),
('IC-C-STRUCT', 'Structures de Données', 8, 2, 0.30, 0.30, 0.40, 'pratique'),
('IC-C-TP', 'Travaux Pratiques C', 8, 1, 0.00, 1.00, 0.00, 'pratique');

-- EC pour UE Algorithmique
INSERT INTO `ecs` (`code`, `nom`, `ue_id`, `coefficient`, `coefficient_cc`, `coefficient_tp`, `coefficient_examen`, `type`) VALUES
('IC-ALGO-BASE', 'Algorithmique de Base', 9, 2, 0.40, 0.20, 0.40, 'theorique'),
('IC-ALGO-AV', 'Algorithmique Avancée', 9, 2, 0.40, 0.20, 0.40, 'theorique');

-- EC pour UE Outils Mathématiques
INSERT INTO `ecs` (`code`, `nom`, `ue_id`, `coefficient`, `coefficient_cc`, `coefficient_tp`, `coefficient_examen`, `type`) VALUES
('IC-MATH-ALG', 'Algèbre Linéaire', 7, 3, 0.30, 0.10, 0.60, 'theorique'),
('IC-MATH-ANA', 'Analyse', 7, 3, 0.30, 0.10, 0.60, 'theorique');

-- EC pour Biochimie Générale
INSERT INTO `ecs` (`code`, `nom`, `ue_id`, `coefficient`, `coefficient_cc`, `coefficient_tp`, `coefficient_examen`, `type`) VALUES
('ABB-BIOCHIM-G', 'Biochimie Générale', 15, 3, 0.30, 0.20, 0.50, 'mixed'),
('ABB-ENZYM', 'Enzymologie', 15, 3, 0.30, 0.20, 0.50, 'mixed');

-- EC pour Biologie Cellulaire
INSERT INTO `ecs` (`code`, `nom`, `ue_id`, `coefficient`, `coefficient_cc`, `coefficient_tp`, `coefficient_examen`, `type`) VALUES
('ABB-BIO-CELL', 'Biologie Cellulaire', 14, 3, 0.30, 0.20, 0.50, 'mixed'),
('ABB-GENET', 'Génétique', 14, 3, 0.30, 0.20, 0.50, 'mixed');

-- Enseignants
INSERT INTO `utilisateurs` (`matricule`, `nom`, `prenom`, `sexe`, `date_naissance`, `lieu_naissance`, `nationalite`, `telephone`, `email`, `grade`, `specialite`, `institut_id`, `actif`) VALUES
('ENS-001', 'Ngouala', 'Jean-Pierre', 'M', '1975-03-15', 'Franceville', 'Gabonaise', '+241 06 44 55 66', 'ngouala@cuk-gabon.ga', 'MC', 'Informatique', 1, 1),
('ENS-002', 'Mouyama', 'Marie', 'F', '1980-07-22', 'Moanda', 'Gabonaise', '+241 07 77 88 99', 'mouyama@cuk-gabon.ga', 'MC', 'Chimie', 1, 1),
('ENS-003', 'Mouckagno', 'Pierre', 'M', '1972-11-08', 'Koulamoutou', 'Gabonaise', '+241 06 12 34 56', 'mouckagno@cuk-gabon.ga', 'PR', 'Mathématiques', 1, 1),
('ENS-004', 'Nguema', 'Florence', 'F', '1985-01-30', 'Libreville', 'Gabonaise', '+241 07 98 76 54', 'nguema@cuk-gabon.ga', 'MC', 'Biochimie', 2, 1),
('ENS-005', 'Obame', 'Paul', 'M', '1978-05-12', 'Lastourville', 'Gabonaise', '+241 06 23 45 67', 'obame@cuk-gabon.ga', 'MC', 'Physique', 1, 1),
('ENS-006', 'Issanga', 'Marie-Madeleine', 'F', '1982-09-25', 'Moabi', 'Gabonaise', '+241 07 34 56 78', 'issanga@cuk-gabon.ga', 'MC', 'Biologie', 2, 1);

-- Paramètres par défaut
INSERT INTO `parametres` (`cle`, `valeur`, `description`, `categorie`) VALUES
('nom_etablissement', 'Centre Universitaire de Koulamoutou', 'Nom officiel de l''établissement', 'general'),
('sigle', 'CUK', 'Sigle de l''établissement', 'general'),
('adresse', 'Koulamoutou, Province de l''Ogooué-Lolo, Gabon', 'Adresse de l''établissement', 'general'),
('telephone', '+241 01 XX XX XX', 'Téléphone de l''établissement', 'general'),
('email', 'contact@cuk-gabon.ga', 'Email de l''établissement', 'general'),
('directeur', 'Dr. Bernard IVOT', 'Nom du directeur', 'general'),
('seuil_reussite', '10', 'Note minimale de validation (sur 20)', 'notation'),
('seuil_bourse', '12', 'Note minimale pour bourse d''études', 'notation'),
('seuil_honneur', '14', 'Note minimale pour mention d''honneur', 'notation'),
('coef_cc', '0.20', 'Coefficient par défaut pour CC', 'notation'),
('coef_tp', '0.20', 'Coefficient par défaut pour TP', 'notation'),
('coef_examen', '0.60', 'Coefficient par défaut pour Examen', 'notation'),
('credits_passage', '60', 'Crédits minimum pour passage au semestre suivant', 'notation'),
('credits_dut', '120', 'Crédits total pour obtenir le DUT', 'notation'),
('nb_etudiants_max', '50', 'Nombre maximum d''étudiants par classe', 'inscription');

-- Exemple d'étudiants
INSERT INTO `etudiants` (`numero`, `matricule`, `nom`, `prenom`, `sexe`, `date_naissance`, `lieu_naissance`, `nationalite`, `telephone`, `email`, `adresse`, `filiere_id`, `semestre`, `annee_academique_id`, `date_inscription`, `statut`, `boursier`, `observation`) VALUES
('ETU-2025-001', 'MAT-2025-001', 'Mekui', 'Brice', 'M', '2002-05-12', 'Koulamoutou', 'Gabonaise', '+241 07 11 22 33', 'mekui.brice@etu.cuk-gabon.ga', 'Quartier Centre, Koulamoutou', 4, 'S1', 2, '2025-10-05', 'actif', 1, 'Boursier d''État'),
('ETU-2025-002', 'MAT-2025-002', 'Obame', 'Christelle', 'F', '2001-08-25', 'Lastourville', 'Gabonaise', '+241 07 44 55 66', 'obame.christelle@etu.cuk-gabon.ga', 'Quartier Plateau, Koulamoutou', 4, 'S1', 2, '2025-10-06', 'actif', 0, NULL),
('ETU-2025-003', 'MAT-2025-003', 'Moussavou', 'Armel', 'M', '2003-02-14', 'Moanda', 'Gabonaise', '+241 06 77 88 99', 'moussavou.armel@etu.cuk-gabon.ga', 'Quartier Bahaut, Koulamoutou', 6, 'S1', 2, '2025-10-07', 'actif', 1, 'Boursier d''État'),
('ETU-2025-004', 'MAT-2025-004', 'Nguema', 'Sonia', 'F', '2002-11-03', 'Libreville', 'Gabonaise', '+241 07 22 33 44', 'nguema.sonia@etu.cuk-gabon.ga', 'Quartier Kennedy, Koulamoutou', 4, 'S1', 2, '2025-10-08', 'actif', 0, 'Transfert depuis l''USMB'),
('ETU-2025-005', 'MAT-2025-005', 'Mabika', 'Didier', 'M', '2000-06-18', 'Koulamoutou', 'Gabonaise', '+241 06 55 66 77', 'mabika.didier@etu.cuk-gabon.ga', 'Quartier Commerce, Koulamoutou', 6, 'S1', 2, '2025-10-05', 'actif', 0, NULL),
('ETU-2025-006', 'MAT-2025-006', 'Adaghe', 'Esther', 'F', '2002-03-22', 'Koulamoutou', 'Gabonaise', '+241 07 66 77 88', 'adaghe.esther@etu.cuk-gabon.ga', 'Quartier Centre, Koulamoutou', 1, 'S1', 2, '2025-10-09', 'actif', 0, NULL),
('ETU-2025-007', 'MAT-2025-007', 'Mve', 'Jean-Marc', 'M', '2001-09-15', 'Moanda', 'Gabonaise', '+241 06 88 99 00', 'mve.jeanmarc@etu.cuk-gabon.ga', 'Quartier Bahaut, Koulamoutou', 3, 'S1', 2, '2025-10-10', 'actif', 1, 'Boursier d''État');

-- Notes d'exemple pour ETU-2025-001 (Informatique S1)
INSERT INTO `notes` (`etudiant_id`, `ec_id`, `annee_academique_id`, `cc`, `tp`, `examen`, `saisi_par`, `valide`) VALUES
(1, 20, 2, 14.00, 14.00, 15.50, 2, 1),
(1, 21, 2, 12.00, NULL, 13.00, 2, 1),
(1, 22, 2, 15.00, 14.00, NULL, 2, 1),
(1, 23, 2, 10.00, NULL, 11.00, 2, 1),
(1, 24, 2, 14.00, NULL, 15.00, 2, 1),
(1, 25, 2, 13.00, NULL, 14.00, 2, 1);

-- Notes pour ETU-2025-003 (Analyses Biologiques)
INSERT INTO `notes` (`etudiant_id`, `ec_id`, `annee_academique_id`, `cc`, `tp`, `examen`, `saisi_par`, `valide`) VALUES
(3, 28, 2, 14.00, 13.00, 15.00, 2, 1),
(3, 29, 2, 12.00, NULL, 13.50, 2, 1),
(3, 30, 2, 15.00, 14.00, 16.00, 2, 1),
(3, 31, 2, 13.00, NULL, 14.00, 2, 1);

-- Incident d'exemple
INSERT INTO `incidents` (`etudiant_id`, `type`, `description`, `gravite`, `date_incident`, `lieu`, `utilisateur_id`, `mesures`, `statut`) VALUES
(2, 'retard', 'Retard de 15 minutes en cours de Programmation C', 'mineur', '2025-11-20', 'Salle B201', 2, 'Avertissement oral', 'cloture');

-- Absence d'exemple
INSERT INTO `absences` (`etudiant_id`, `ec_id`, `annee_academique_id`, `date_absence`, `nombre_heures`, `justifiee`, `motif`, `saisi_par`) VALUES
(2, 20, 2, '2025-11-18', 2, 1, 'Rendez-vous médical', 2),
(3, 28, 2, '2025-11-22', 3, 0, 'Non justifié', 2);

-- Notification d'exemple
INSERT INTO `notifications` (`user_id`, `type`, `titre`, `message`) VALUES
(1, 'info', 'Bienvenue sur CUK-Admin', 'Bienvenue sur le système de gestion du Centre Universitaire de Koulamoutou.'),
(2, 'info', 'Inscriptions ouvertes', 'Les inscriptions pour l''année académique 2025-2026 sont ouvertes.');