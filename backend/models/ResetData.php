<?php
// Modèle pour la réinitialisation des données de test
// Toutes les requêtes SQL liées à la réinitialisation sont centralisées ici

class ResetData {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Supprime toutes les participations
     */
    public function deleteParticipations() {
        $stmt = $this->pdo->prepare("DELETE FROM participation");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Supprime toutes les opérations de crédit
     */
    public function deleteCreditOperations() {
        $stmt = $this->pdo->prepare("DELETE FROM credit_operation");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Remet du crédit à tous les utilisateurs
     */
    public function resetUserCredits($montant = 100.00) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET credit = ?");
        $stmt->execute([$montant]);
        return $stmt->rowCount();
    }

    /**
     * Récupère l'état des utilisateurs
     */
    public function getUsers() {
        $stmt = $this->pdo->query("SELECT utilisateur_id, nom, prenom, email, credit FROM utilisateur ORDER BY utilisateur_id");
        return $stmt->fetchAll();
    }

    /**
     * Récupère l'état des covoiturages planifiés
     */
    public function getCovoiturages() {
        $stmt = $this->pdo->query("SELECT covoiturage_id, lieu_depart, lieu_arrivee, prix_personne, nb_places, date_depart FROM covoiturage WHERE statut = 'planifie' ORDER BY covoiturage_id");
        return $stmt->fetchAll();
    }
}
