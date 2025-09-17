<?php
// Service pour la logique métier liée aux participations d'un utilisateur
// Ce service utilise le modèle Participation

require_once __DIR__ . '/../models/Participation.php';

class ParticipationService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new Participation($pdo);
    }

    /**
     * Récupère toutes les participations d'un utilisateur
     */
    public function getUserParticipations($userId) {
        $participations = $this->model->getParticipationsByUser($userId);
        return [
            'success' => true,
            'participations' => $participations
        ];
    }
}
