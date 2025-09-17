<?php
// services/CovoiturageStatutService.php
// Service pour la logique métier liée au changement de statut d'un covoiturage (démarrage, arrêt, etc.)
// Ce fichier centralise les vérifications, la validation et l'appel au modèle CovoiturageStatut

require_once __DIR__ . '/../models/CovoiturageStatut.php';

class CovoiturageStatutService
{
    private $pdo;
    private $statutModel;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->statutModel = new CovoiturageStatut($pdo);
    }

    // Fonction principale pour démarrer un covoiturage
    public function demarrer($user_id, $data)
    {
        $covoiturage_id = isset($data['covoiturage_id']) ? intval($data['covoiturage_id']) : 0;

        // Vérifier que l'ID du covoiturage est fourni
        if (!$covoiturage_id) {
            return ['success' => false, 'message' => 'ID du covoiturage manquant'];
        }

        // Récupérer le covoiturage et vérifier le conducteur
        $covoiturage = $this->statutModel->getCovoiturageById($covoiturage_id);
        if (!$covoiturage || $covoiturage['conducteur_id'] != $user_id) {
            return ['success' => false, 'message' => 'Covoiturage non trouvé ou non autorisé'];
        }
        if ($covoiturage['statut'] !== 'planifie') {
            return ['success' => false, 'message' => 'Ce covoiturage ne peut pas être démarré'];
        }

        // Changer le statut du covoiturage en "en_cours"
        $this->statutModel->changerStatut($covoiturage_id, 'en_cours');

        // Réponse de succès
        return [
            'success' => true,
            'message' => 'Covoiturage démarré avec succès !'
        ];
    }
}
