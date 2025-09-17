-- EcoRide - Seed de démonstration (à importer via phpMyAdmin)
-- OPTION 1 (recommandée): Générez une version prête automatiquement:
--   php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql
--   Puis importez backend/seed_demo_ready.sql dans phpMyAdmin.
-- OPTION 2: Remplacez manuellement les <HASH_...> par des hash (password_hash)
-- Exemple PowerShell:
--   php -r "echo password_hash('Admin@123!', PASSWORD_DEFAULT), PHP_EOL;"

START TRANSACTION;

-- 1) Comptes (admin, employé, chauffeur, passager)
INSERT IGNORE INTO utilisateur (nom, prenom, email, pseudo, password, credit, type_utilisateur)
VALUES
 ('Admin','Demo','admin@ecoride.test','AdminDemo','<HASH_ADMIN>', 100.00, 'administrateur'),
 ('Employe','Demo','employe@ecoride.test','EmployeDemo','<HASH_EMP>', 50.00, 'employe'),
 ('Driver','Demo','driver@ecoride.test','DriverDemo','<HASH_DRIVER>', 40.00, 'standard'),
 ('Passenger','Demo','passenger@ecoride.test','PassengerDemo','<HASH_PASS>', 20.00, 'standard');

-- 2) Récupération des IDs
SET @admin_id     = (SELECT utilisateur_id FROM utilisateur WHERE email='admin@ecoride.test');
SET @employe_id   = (SELECT utilisateur_id FROM utilisateur WHERE email='employe@ecoride.test');
SET @driver_id    = (SELECT utilisateur_id FROM utilisateur WHERE email='driver@ecoride.test');
SET @passenger_id = (SELECT utilisateur_id FROM utilisateur WHERE email='passenger@ecoride.test');

-- 3) Marques
INSERT IGNORE INTO marque (libelle) VALUES ('Tesla'), ('Renault');
SET @marque_tesla   = (SELECT marque_id FROM marque WHERE libelle='Tesla');
SET @marque_renault = (SELECT marque_id FROM marque WHERE libelle='Renault');

-- 4) Véhicules du chauffeur (un électrique, un thermique)
INSERT IGNORE INTO voiture (modele, marque_id, immatriculation, energie, nb_places, est_ecologique, couleur, date_premiere_immatriculation, utilisateur_id)
VALUES
 ('Model 3', @marque_tesla,   'TEST-EL-123', 'electrique', 4, 1, 'noir',  '2022-01-10', @driver_id),
 ('Clio IV',  @marque_renault, 'TEST-ES-456', 'essence',    4, 0, 'bleu',  '2019-05-20', @driver_id);

SET @v_elec_id = (SELECT voiture_id FROM voiture WHERE immatriculation='TEST-EL-123');
SET @v_therm_id = (SELECT voiture_id FROM voiture WHERE immatriculation='TEST-ES-456');

-- 5) Covoiturages de démo (à venir; l’un écologique)
INSERT INTO covoiturage (date_depart, heure_depart, lieu_depart, heure_arrivee, lieu_arrivee, nb_places, prix_personne, statut, est_ecologique, conducteur_id, voiture_id)
VALUES
 ('2025-09-20', '08:00:00', 'Paris',  '10:30:00', 'Orléans',  3, 12.50, 'planifie', 1, @driver_id, @v_elec_id),
 ('2025-09-21', '18:00:00', 'Lyon',   '21:00:00', 'Valence',  2, 10.00, 'planifie', 0, @driver_id, @v_therm_id);

COMMIT;

-- Après import: connectez-vous avec les comptes créés (emails ci-dessus),
-- mots de passe correspondant aux hash que vous avez générés.
