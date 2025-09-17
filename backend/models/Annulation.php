<?php
// models/Annulation.php
// Modèle pour la gestion des annulations de covoiturage et de participation
// Ce fichier contient les fonctions d'accès à la base de données pour les annulations

class Annulation
{
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // a. Récupérer un covoiturage par son id et vérifier le conducteur
    public function getCovoiturageById($covoiturage_id)
    {
        //  on ne sélectionne que les colonnes qui existent sûrement.
        // (Amélioration possible plus tard : détecter les colonnes et enrichir.)
        $sql = "SELECT conducteur_id, statut FROM covoiturage WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetch();
    }

    // Marquer un covoiturage comme annulé
    public function annulerCovoiturage($covoiturage_id)
    {
        $sql = "UPDATE covoiturage SET statut = 'annule' WHERE covoiturage_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$covoiturage_id]);
    }

    // b. Récupérer une participation par id et utilisateur
    public function getParticipationById($participation_id, $user_id)
    {
        $sql = "SELECT statut FROM participation WHERE participation_id = ? AND utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$participation_id, $user_id]);
        return $stmt->fetch();
    }

    // Marquer une participation comme annulée
    public function annulerParticipation($participation_id)
    {
        $sql = "UPDATE participation SET statut = 'annulee' WHERE participation_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$participation_id]);
    }
}
