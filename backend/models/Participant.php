<?php
// backend/models/Participant.php
// Modèle pour récupérer les participants d'un covoiturage

class Participant {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // Récupère les emails des participants d'un covoiturage
    public function getEmailsByCovoiturage($covoiturage_id) {
        $sql = "SELECT u.email FROM participation p JOIN utilisateur u ON p.utilisateur_id = u.utilisateur_id WHERE p.covoiturage_id = ? AND p.statut = 'en_attente_validation'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupère l'email du chauffeur
    public function getChauffeurEmail($covoiturage_id) {
        $sql = "SELECT u.email FROM covoiturage c JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id WHERE c.covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetchColumn();
    }
}
