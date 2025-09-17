<?php
// Modèle pour l'accès aux données du profil utilisateur
// Toutes les requêtes SQL liées au profil sont centralisées ici

class UserProfile {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les informations complètes de l'utilisateur (profil, nb covoiturages, nb participations)
     */
    public function getUserProfile($userId) {
        $sql = "
            SELECT u.*, 
                   COUNT(DISTINCT c.covoiturage_id) as nb_covoiturages,
                   COUNT(DISTINCT p.participation_id) as nb_participations
            FROM utilisateur u
            LEFT JOIN covoiturage c ON u.utilisateur_id = c.conducteur_id
            LEFT JOIN participation p ON u.utilisateur_id = p.utilisateur_id AND p.statut IN ('en_attente_validation','validee','probleme','annulee','confirmee')
            WHERE u.utilisateur_id = ?
            GROUP BY u.utilisateur_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si l'utilisateur possède au moins une voiture (est chauffeur)
     */
    public function isChauffeur($userId) {
        $sql = 'SELECT COUNT(*) FROM voiture WHERE utilisateur_id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0;
    }
}
