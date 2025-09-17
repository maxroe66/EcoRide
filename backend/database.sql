-- Base de données EcoRide - Structure réelle mise à jour

CREATE TABLE IF NOT EXISTS utilisateur (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    pseudo VARCHAR(50) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    credit DECIMAL(10,2) DEFAULT 0.00,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    type_utilisateur ENUM('standard', 'employe', 'administrateur') DEFAULT 'standard',
    suspendu TINYINT(1) NOT NULL DEFAULT 0,
    preference_fumeur ENUM('accepte', 'refuse'),
    preference_animaux ENUM('accepte', 'refuse'),
    autres_preferences TEXT
);

-- Table marque (MCD)
CREATE TABLE IF NOT EXISTS marque (
    marque_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

-- Table voiture
CREATE TABLE IF NOT EXISTS voiture (
    voiture_id INT AUTO_INCREMENT PRIMARY KEY,
    modele VARCHAR(100) NOT NULL,
    -- Remplace l'ancien champ texte 'marque' par une clé étrangère vers 'marque'
    marque_id INT NOT NULL,
    immatriculation VARCHAR(20) NOT NULL UNIQUE,
    energie ENUM('essence', 'diesel', 'electrique', 'hybride') NOT NULL,
    nb_places INT NOT NULL DEFAULT 4,
    est_ecologique TINYINT(1) DEFAULT 0,
    couleur VARCHAR(50),
    date_premiere_immatriculation DATE NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (marque_id) REFERENCES marque(marque_id)
);

-- Table covoiturage
CREATE TABLE IF NOT EXISTS covoiturage (
    covoiturage_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Aligne le MCD : date_depart (DATE) + heure_depart / heure_arrivee (TIME)
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    lieu_depart VARCHAR(255) NOT NULL,
    heure_arrivee TIME,
    lieu_arrivee VARCHAR(255) NOT NULL,
    nb_places INT NOT NULL,
    prix_personne DECIMAL(8,2) NOT NULL,
    statut ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    est_ecologique TINYINT(1) DEFAULT 0,
    conducteur_id INT NOT NULL,
    voiture_id INT NOT NULL,
    FOREIGN KEY (conducteur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (voiture_id) REFERENCES voiture(voiture_id)
);

-- Table participation
CREATE TABLE IF NOT EXISTS participation (
    participation_id INT AUTO_INCREMENT PRIMARY KEY,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- Statuts attendus par l'application :
    -- 'demandee' (par défaut), 'confirmee' (réservation acceptée),
    -- 'annulee' (annulation), 'refusee' (refus),
    -- 'en_attente_validation' (après fin de trajet, en attente confirmation passager),
    -- 'validee' (trajet validé positivement), 'probleme' (signalement négatif)
    statut ENUM('demandee', 'confirmee', 'refusee', 'annulee', 'en_attente_validation', 'validee', 'probleme') DEFAULT 'demandee',
    nb_places INT NOT NULL DEFAULT 1,
    utilisateur_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id)
);

-- Table incident 
CREATE TABLE IF NOT EXISTS incident (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    participation_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    description TEXT NULL,
    statut ENUM('en_cours','resolu') DEFAULT 'en_cours',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_participation (participation_id),
    INDEX idx_covoiturage (covoiturage_id),
    INDEX idx_utilisateur (utilisateur_id),
    CONSTRAINT fk_incident_participation FOREIGN KEY (participation_id) REFERENCES participation(participation_id),
    CONSTRAINT fk_incident_covoiturage FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id),
    CONSTRAINT fk_incident_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table credit_operation
CREATE TABLE IF NOT EXISTS credit_operation (
    operation_id INT AUTO_INCREMENT PRIMARY KEY,
    date_operation DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_operation ENUM('credit', 'debit') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
);

-- Table avis_fallback
CREATE TABLE IF NOT EXISTS avis_fallback (
    avis_id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    note INT NOT NULL CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(covoiturage_id)
);
-- Table pour gérer les fonds bloqués (escrow) avant versement chauffeur
CREATE TABLE IF NOT EXISTS escrow_transaction (
  escrow_id INT AUTO_INCREMENT PRIMARY KEY,
  participation_id INT NOT NULL,
  passager_id INT NOT NULL,
  chauffeur_id INT NOT NULL,
  montant_brut DECIMAL(10,2) NOT NULL,
  commission DECIMAL(10,2) NOT NULL DEFAULT 0,
  statut ENUM('pending','released','refunded') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_participation (participation_id),
  KEY idx_passager (passager_id),
  KEY idx_chauffeur (chauffeur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

