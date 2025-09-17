<?php
// Service pour la logique métier liée au profil utilisateur
// Ce service utilise le modèle UserProfile

require_once __DIR__ . '/../models/UserProfile.php';

class UserProfileService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new UserProfile($pdo);
    }

    /**
     * Récupère le profil complet d'un utilisateur
     */
    public function getProfile($userId) {
        $user = $this->model->getUserProfile($userId);
        $isChauffeur = $this->model->isChauffeur($userId);
        if ($user) {
            unset($user['password']); // On ne renvoie jamais le mot de passe !
            $user['is_chauffeur'] = $isChauffeur;
            // Normalisation simple des préférences dans un tableau lisible (pour tests US8)
            $prefs = [];
            if (isset($user['preference_fumeur'])) {
                $prefs[] = ($user['preference_fumeur'] === 'refuse') ? 'Non-fumeur' : 'Fumeur accepté';
            }
            if (isset($user['preference_animaux'])) {
                $prefs[] = ($user['preference_animaux'] === 'refuse') ? 'Animaux non autorisés' : 'Animaux autorisés';
            }
            if (!empty($user['autres_preferences'])) { $prefs[] = $user['autres_preferences']; }
            $user['preferences'] = $prefs;
            return [
                'success' => true,
                'user' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }
    }
}
