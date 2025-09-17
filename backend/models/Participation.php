<?php
// Modèle pour l'accès aux données des participations d'un utilisateur
// Toutes les requêtes SQL liées aux participations sont centralisées ici

class Participation {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère toutes les participations d'un utilisateur
     */
    public function getParticipationsByUser($userId) {
        $sql = "
            SELECT p.participation_id, p.nb_places, p.statut, p.date_reservation,
                   c.lieu_depart, c.lieu_arrivee, c.date_depart, c.prix_personne
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            WHERE p.utilisateur_id = ?
            ORDER BY c.date_depart DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
