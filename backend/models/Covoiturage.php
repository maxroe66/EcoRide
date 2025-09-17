<?php
// Ce fichier contient les fonctions d'accès à la base de données pour les covoiturages

class Covoiturage
{
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // 2. Vérifier que l'utilisateur a au moins un véhicule
    public function utilisateurPossedeVehicule($user_id)
    {
        $sql = "SELECT COUNT(*) FROM voiture WHERE utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $nb_vehicules = $stmt->fetchColumn();
        return $nb_vehicules > 0;
    }

    // 5. Vérifier que le véhicule appartient bien à l'utilisateur
    public function vehiculeAppartientAUtilisateur($vehicule_id, $user_id)
    {
        $sql = "SELECT * FROM voiture WHERE voiture_id = ? AND utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vehicule_id, $user_id]);
        return $stmt->fetch();
    }

    // 7. Insérer le covoiturage dans la base de données
    public function insererCovoiturage($user_id, $vehicule_id, $lieu_depart, $lieu_arrivee, $date_depart, $heure_depart, $heure_arrivee, $prix, $nb_places)
    {
        $sql = "INSERT INTO covoiturage (conducteur_id, voiture_id, lieu_depart, lieu_arrivee, date_depart, heure_depart, heure_arrivee, prix_personne, nb_places) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$user_id, $vehicule_id, $lieu_depart, $lieu_arrivee, $date_depart, $heure_depart, $heure_arrivee, $prix, $nb_places]);
    }

    // 8. Si tout s'est bien passé, on retire 2 crédits au chauffeur
    public function retirerCredits($user_id, $credits = 2)
    {
        // Débit sécurisé pour la commission: évite solde négatif si comptes de test modifiés
        if ($credits <= 0) { return true; }
        $beforeStmt = $this->pdo->prepare("SELECT credit FROM utilisateur WHERE utilisateur_id = ?");
        $beforeStmt->execute([$user_id]);
        $avant = (float)$beforeStmt->fetchColumn();

        $this->pdo->prepare("UPDATE utilisateur SET credit = CASE WHEN credit >= ? THEN credit - ? ELSE credit END WHERE utilisateur_id = ?")
            ->execute([$credits, $credits, $user_id]);

        $afterStmt = $this->pdo->prepare("SELECT credit FROM utilisateur WHERE utilisateur_id = ?");
        $afterStmt->execute([$user_id]);
        $apres = (float)$afterStmt->fetchColumn();
        return ($avant - $apres) >= $credits - 0.0001;
    }
}
