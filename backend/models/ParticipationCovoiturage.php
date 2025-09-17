<?php
// Modèle pour la gestion de la participation à un covoiturage

class ParticipationCovoiturage {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    // Permet d'accéder à l'objet PDO pour les transactions
    public function getPDO() {
        return $this->pdo;
    }

    public function participationExiste($user_id, $covoiturage_id) {
        $stmt = $this->pdo->prepare("SELECT participation_id FROM participation WHERE utilisateur_id = ? AND covoiturage_id = ?");
        $stmt->execute([$user_id, $covoiturage_id]);
        return $stmt->fetch();
    }

    public function getCovoiturage($covoiturage_id) {
        $stmt = $this->pdo->prepare("SELECT prix_personne, nb_places FROM covoiturage WHERE covoiturage_id = ?");
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetch();
    }

    public function getCredit($user_id) {
        $stmt = $this->pdo->prepare("SELECT credit FROM utilisateur WHERE utilisateur_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    public function getPlacesPrises($covoiturage_id) {
    // Comptabilise les places occupées pour les statuts actifs (réservation confirmée ou en validation post-trajet)
    $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(nb_places), 0) as places_prises FROM participation WHERE covoiturage_id = ? AND statut IN ('confirmee','en_attente_validation','validee','probleme')");
        $stmt->execute([$covoiturage_id]);
        return $stmt->fetch();
    }

    public function ajouterParticipation($user_id, $covoiturage_id, $nb_places) {
        $stmt = $this->pdo->prepare("INSERT INTO participation (utilisateur_id, covoiturage_id, nb_places, statut) VALUES (?, ?, ?, 'confirmee')");
        return $stmt->execute([$user_id, $covoiturage_id, $nb_places]);
    }

    public function debiterCredit($user_id, $montant) {
        // Débit sécurisé : ne soustrait que si le solde est suffisant
        if ($montant <= 0) { return true; }
        // Option : récupérer le solde avant pour détecter si l'opération a bien eu lieu
        $stmtBefore = $this->pdo->prepare("SELECT credit FROM utilisateur WHERE utilisateur_id = ?");
        $stmtBefore->execute([$user_id]);
        $avant = (float)$stmtBefore->fetchColumn();

        $this->pdo->prepare("UPDATE utilisateur SET credit = CASE WHEN credit >= ? THEN credit - ? ELSE credit END WHERE utilisateur_id = ?")
            ->execute([$montant, $montant, $user_id]);

        $stmtAfter = $this->pdo->prepare("SELECT credit FROM utilisateur WHERE utilisateur_id = ?");
        $stmtAfter->execute([$user_id]);
        $apres = (float)$stmtAfter->fetchColumn();

        // Succès si le crédit a diminué exactement du montant demandé
        return ($avant - $apres) >= $montant - 0.0001; // tolérance flottante
    }

    public function enregistrerOperation($user_id, $montant) {
        $stmt = $this->pdo->prepare("INSERT INTO credit_operation (utilisateur_id, type_operation, montant) VALUES (?, 'debit', ?)");
        return $stmt->execute([$user_id, $montant]);
    }

    // Suppression de la décrémentation permanente de nb_places : on garde la capacité initiale.
}
