<?php
// models/Vehicule.php
// Modèle pour la gestion des véhicules et des marques
// Ce fichier contient les fonctions d'accès à la base de données pour les véhicules

class Vehicule
{
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère la liste des véhicules d'un utilisateur (id, marque, modèle, couleur, immatriculation)
     */
    public function getVehiculesUtilisateur($userId)
    {
        $sql = 'SELECT v.voiture_id, m.libelle AS marque, v.modele, v.couleur, v.immatriculation FROM voiture v LEFT JOIN marque m ON v.marque_id = m.marque_id WHERE v.utilisateur_id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Vérifier que l'utilisateur est connecté (à faire côté contrôleur)

    // Vérifier que tous les champs sont remplis (à faire côté service)

    // Vérifier que l'énergie est valide (à faire côté service)

    // Vérifier le format de la date (à faire côté service)

    // 10. Vérifier unicité de la plaque
    public function plaqueExiste($plaque)
    {
        $sql = "SELECT COUNT(*) FROM voiture WHERE immatriculation = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$plaque]);
        return $stmt->fetchColumn() > 0;
    }

    // 11. Vérifier si c'est le premier véhicule (devenir chauffeur)
    public function estPremierVehicule($user_id)
    {
        $sql = "SELECT COUNT(*) FROM voiture WHERE utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() == 0;
    }

    // 15. Récupérer ou créer la marque
    public function getOrCreateMarque($marque)
    {
        $sql = "SELECT marque_id FROM marque WHERE libelle = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$marque]);
        $marqueRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($marqueRow) {
            return (int)$marqueRow['marque_id'];
        } else {
            $sql = "INSERT INTO marque (libelle) VALUES (?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$marque]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    // 16. Insérer le véhicule dans la base
    public function insererVehicule($modele, $marque_id, $plaque, $energie, $nb_places, $est_ecologique, $couleur, $date_premiere_immatriculation, $user_id)
    {
        $sql = "INSERT INTO voiture (modele, marque_id, immatriculation, energie, nb_places, est_ecologique, couleur, date_premiere_immatriculation, utilisateur_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$modele, $marque_id, $plaque, $energie, $nb_places, $est_ecologique, $couleur, $date_premiere_immatriculation, $user_id]);
    }

    // 17. Si c'est pour devenir chauffeur, sauvegarder les préférences
    public function updatePreferences($user_id, $preferences)
    {
        $sql = "UPDATE utilisateur SET preference_fumeur = ?, preference_animaux = ?, autres_preferences = ? WHERE utilisateur_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $preferences['fumeur'],
            $preferences['animaux'],
            isset($preferences['autres_preferences']) ? $preferences['autres_preferences'] : '',
            $user_id
        ]);
    }
}
