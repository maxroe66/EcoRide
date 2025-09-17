<?php
// services/VehiculeService.php
// Service pour la logique métier liée à l'ajout de véhicule
// Ce fichier centralise les vérifications, la validation et la transaction

require_once __DIR__ . '/../models/Vehicule.php';

class VehiculeService
{
    private $pdo;
    private $vehiculeModel;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->vehiculeModel = new Vehicule($pdo);
    }

    // Fonction principale pour ajouter un véhicule
    public function ajouterVehicule($user_id, $data)
    {
        // 6. On récupère chaque champ du formulaire
        $marque = isset($data['marque']) ? trim($data['marque']) : '';
        $modele = isset($data['modele']) ? trim($data['modele']) : '';
        $couleur = isset($data['couleur']) ? trim($data['couleur']) : '';
        $plaque = isset($data['plaque']) ? trim($data['plaque']) : '';
        $energie = isset($data['energie']) ? strtolower(trim($data['energie'])) : '';
        $date_premiere_immatriculation = isset($data['date_premiere_immatriculation']) ? $data['date_premiere_immatriculation'] : '';

        // 7. Vérifier que tous les champs sont remplis
        if (!$marque || !$modele || !$couleur || !$plaque || !$energie || !$date_premiere_immatriculation) {
            return ['success' => false, 'message' => 'Merci de remplir tous les champs du véhicule.'];
        }

        // 8. Vérifier que l'énergie est valide
        if (!in_array($energie, ['essence', 'diesel', 'electrique', 'hybride'], true)) {
            return ['success' => false, 'message' => "Énergie invalide."];
        }

        // 9. Vérifier le format de la date (AAAA-MM-JJ)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_premiere_immatriculation)) {
            return ['success' => false, 'message' => 'Format de date invalide (AAAA-MM-JJ).'];
        }

        // 10. Vérifier unicité de la plaque
        if ($this->vehiculeModel->plaqueExiste($plaque)) {
            return ['success' => false, 'message' => 'Cette plaque est déjà enregistrée.'];
        }

        // 11. Vérifier si c'est le premier véhicule (devenir chauffeur)
        $isDevenirChauffeur = $this->vehiculeModel->estPremierVehicule($user_id);

        // 12. Si c'est pour devenir chauffeur, vérifier les préférences
        $preferences = null;
        if ($isDevenirChauffeur) {
            $preferences = isset($data['preferences']) ? $data['preferences'] : null;
            if (!$preferences || !isset($preferences['fumeur']) || !isset($preferences['animaux'])) {
                return ['success' => false, 'message' => 'Les préférences sont obligatoires pour devenir chauffeur.'];
            }
            if ($preferences['fumeur'] === '' || $preferences['animaux'] === '') {
                return ['success' => false, 'message' => 'Veuillez remplir toutes les préférences obligatoires.'];
            }
        }

        // 13. Déterminer si le véhicule est écologique
        $est_ecologique = in_array($energie, ['electrique', 'hybride'], true) ? 1 : 0;

        try {
            // 14. On commence une transaction pour garantir la cohérence
            $this->pdo->beginTransaction();

            // 15. Récupérer ou créer la marque
            $marque_id = $this->vehiculeModel->getOrCreateMarque($marque);

            // 16. Insérer le véhicule dans la base
            $ok = $this->vehiculeModel->insererVehicule($modele, $marque_id, $plaque, $energie, 4, $est_ecologique, $couleur, $date_premiere_immatriculation, $user_id);

            // 17. Si c'est pour devenir chauffeur, sauvegarder les préférences
            if ($ok && $isDevenirChauffeur && $preferences) {
                $this->vehiculeModel->updatePreferences($user_id, $preferences);
            }

            // 18. Valider la transaction
            $this->pdo->commit();

            // 19. Message de succès
            $message = $isDevenirChauffeur ? 'Félicitations ! Vous êtes maintenant chauffeur.' : 'Véhicule ajouté avec succès.';
            return ['success' => true, 'message' => $message];

        } catch (Exception $e) {
            // 20. En cas d'erreur, annuler la transaction
            $this->pdo->rollback();
            return ['success' => false, 'message' => "Erreur lors de l'ajout du véhicule."];
        }
    }
}
