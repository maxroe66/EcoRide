<?php
// Modèle pour l'accès aux données des véhicules du profil utilisateur
// Toutes les requêtes SQL liées aux véhicules du profil sont centralisées ici

class VehiculeProfil {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère la liste des véhicules d'un utilisateur
     */
    public function getVehiculesByUser($userId) {
        $sql = 'SELECT v.modele, m.libelle AS marque, v.immatriculation, v.energie, v.nb_places, v.est_ecologique, v.couleur, v.date_premiere_immatriculation FROM voiture v LEFT JOIN marque m ON v.marque_id = m.marque_id WHERE v.utilisateur_id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
