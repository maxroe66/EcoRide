<?php
// Ce fichier centralise les vérifications et appels au modèle

require_once __DIR__ . '/../models/Covoiturage.php';

class CovoiturageService
{
    private $pdo;
    private $covoiturageModel;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->covoiturageModel = new Covoiturage($pdo);
    }

    // Fonction principale pour ajouter un covoiturage
    public function ajouterCovoiturage($user_id, $data)
    {
        // 2. Vérifier que l'utilisateur a au moins un véhicule
        if (!$this->covoiturageModel->utilisateurPossedeVehicule($user_id)) {
            return ['success' => false, 'message' => 'Vous devez enregistrer un véhicule avant de proposer un covoiturage.'];
        }

        // 3. Récupérer les données envoyées par le formulaire (en JSON)
        $lieu_depart = isset($data['lieu_depart']) ? trim($data['lieu_depart']) : '';
        $lieu_arrivee = isset($data['lieu_arrivee']) ? trim($data['lieu_arrivee']) : '';
        $date_depart = isset($data['date_depart']) ? trim($data['date_depart']) : '';
        $heure_depart = isset($data['heure_depart']) ? trim($data['heure_depart']) : '';
        $heure_arrivee = isset($data['heure_arrivee']) ? trim($data['heure_arrivee']) : '';
        $prix = isset($data['prix']) ? floatval($data['prix']) : 0;
        $vehicule_id = isset($data['vehicule_id']) ? intval($data['vehicule_id']) : 0;
        $nb_places = isset($data['nb_places']) ? intval($data['nb_places']) : 1;

        // 4. Vérifier que tous les champs sont remplis et valides
        if (!$lieu_depart || !$lieu_arrivee || !$date_depart || !$heure_depart || !$heure_arrivee || $prix <= 0 || $vehicule_id <= 0 || $nb_places <= 0) {
            return ['success' => false, 'message' => 'Merci de remplir tous les champs correctement.'];
        }

        // 5. Vérifier que le véhicule appartient bien à l'utilisateur
        if (!$this->covoiturageModel->vehiculeAppartientAUtilisateur($vehicule_id, $user_id)) {
            return ['success' => false, 'message' => 'Le véhicule sélectionné ne vous appartient pas.'];
        }

        // 6. Vérifier que l'heure d'arrivée est après l'heure de départ (optionnel mais conseillé)
        if (preg_match('/^\d{2}:\d{2}$/', $heure_depart) && preg_match('/^\d{2}:\d{2}$/', $heure_arrivee)) {
            $timestamp_depart = strtotime($date_depart . ' ' . $heure_depart . ':00');
            $timestamp_arrivee = strtotime($date_depart . ' ' . $heure_arrivee . ':00');
            if ($timestamp_arrivee !== false && $timestamp_depart !== false && $timestamp_arrivee <= $timestamp_depart) {
                return ['success' => false, 'message' => "L'heure d'arrivée doit être après l'heure de départ."];
            }
        }

        // 7. Insérer le covoiturage dans la base de données
        $ok = $this->covoiturageModel->insererCovoiturage($user_id, $vehicule_id, $lieu_depart, $lieu_arrivee, $date_depart, $heure_depart, $heure_arrivee, $prix, $nb_places);

        // 8. Si tout s'est bien passé, on retire 2 crédits au chauffeur et on affiche un message de succès
        if ($ok) {
            $id = $this->pdo->lastInsertId();
            // On retire 2 crédits au conducteur à la création
            $this->covoiturageModel->retirerCredits($user_id, 2);
            return ['success' => true, 'message' => 'Covoiturage ajouté avec succès.', 'covoiturage_id'=>(int)$id];
        } else {
            return ['success' => false, 'message' => "Erreur lors de l'ajout du covoiturage."];
        }
    }
}
