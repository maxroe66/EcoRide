<?php
// Modèle pour la gestion de la terminaison d'un covoiturage
// Toutes les requêtes SQL liées à la terminaison sont centralisées ici

class CovoiturageTerminer {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère le covoiturage par ID
     */
    public function getCovoiturage($covoiturage_id) {
        $sql = "SELECT conducteur_id, statut FROM covoiturage WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetch();
    }

    /**
     * Met à jour le statut du covoiturage en 'termine'
     */
    public function terminerCovoiturage($covoiturage_id) {
        $sql = "UPDATE covoiturage SET statut = 'termine' WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$covoiturage_id]);
    }

    /**
     * Met à jour les participations associées au covoiturage
     */
    public function marquerParticipationsEnAttente($covoiturage_id) {
        $sql = "UPDATE participation SET statut = 'en_attente_validation' WHERE covoiturage_id = ? AND statut = 'confirmee'";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$covoiturage_id]);
    }
}
