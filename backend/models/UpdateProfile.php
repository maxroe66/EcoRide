<?php
// Modèle pour la mise à jour du profil utilisateur

class UpdateProfile {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Met à jour les champs autorisés du profil utilisateur
     */
    public function update($user_id, $data) {
        $champs_autorises = ['nom', 'prenom', 'email', 'telephone'];
        $champs_a_modifier = [];
        $valeurs = [];
        foreach ($champs_autorises as $champ) {
            if (isset($data[$champ]) && !empty($data[$champ])) {
                $champs_a_modifier[] = "$champ = ?";
                $valeurs[] = $data[$champ];
            }
        }
        if (empty($champs_a_modifier)) {
            return false;
        }
        $valeurs[] = $user_id;
        $sql = "UPDATE utilisateur SET " . implode(', ', $champs_a_modifier) . " WHERE utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($valeurs);
    }
}
