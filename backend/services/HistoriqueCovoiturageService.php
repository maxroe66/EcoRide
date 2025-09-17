<?php
// Service pour la logique métier liée à l'historique des covoiturages
// Ce service utilise le modèle HistoriqueCovoiturage

require_once __DIR__ . '/../models/HistoriqueCovoiturage.php';

class HistoriqueCovoiturageService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new HistoriqueCovoiturage($pdo);
    }

    /**
     * Récupère l'historique complet (proposés + participations) pour un utilisateur
     */
    public function getHistorique($userId) {
        // Récupérer les covoiturages proposés
        $covoituragesProposes = $this->model->getCovoituragesProposes($userId);
        // Récupérer les participations
        $participations = $this->model->getParticipations($userId);
        // Préparer la réponse
        return [
            'success' => true,
            'covoiturages_proposes' => $covoituragesProposes,
            'participations' => $participations
        ];
    }
}
