<?php
// Service pour la logique métier liée à la réinitialisation des données de test
// Ce service utilise le modèle ResetData

require_once __DIR__ . '/../models/ResetData.php';

class ResetDataService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new ResetData($pdo);
    }

    /**
     * Effectue la réinitialisation et retourne un rapport structuré
     */
    public function reset() {
        $deleted_participations = $this->model->deleteParticipations();
        $deleted_operations = $this->model->deleteCreditOperations();
        $updated_users = $this->model->resetUserCredits();
        $users = $this->model->getUsers();
        $covoiturages = $this->model->getCovoiturages();
        return [
            'success' => true,
            'deleted_participations' => $deleted_participations,
            'deleted_operations' => $deleted_operations,
            'updated_users' => $updated_users,
            'users' => $users,
            'covoiturages' => $covoiturages
        ];
    }
}
