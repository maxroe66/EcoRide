<?php
// Modèle pour la validation d'une participation à un covoiturage

class ParticipationValidation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Valide une participation pour un utilisateur
     */
    public function valider($participation_id, $user_id) {
        $sql = "UPDATE participation SET statut = 'validee' WHERE participation_id = ? AND utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$participation_id, $user_id]);
        return $stmt->rowCount() > 0;
    }

    // Accès PDO pour opérations complémentaires (débit/crédit différé)
    public function getPDO() { return $this->pdo; }
}
