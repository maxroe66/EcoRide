<?php
// models/CovoiturageStatut.php
// Modèle pour la gestion du statut des covoiturages (démarrage, arrêt, etc.)
// Ce fichier contient les fonctions d'accès à la base de données pour le changement de statut

class CovoiturageStatut
{
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer un covoiturage par son id et vérifier le conducteur
    public function getCovoiturageById($covoiturage_id)
    {
        $sql = "SELECT conducteur_id, statut FROM covoiturage WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetch();
    }

    // Changer le statut du covoiturage
    public function changerStatut($covoiturage_id, $nouveau_statut)
    {
        $sql = "UPDATE covoiturage SET statut = ? WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$nouveau_statut, $covoiturage_id]);
    }
}
