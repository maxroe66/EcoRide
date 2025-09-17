<?php
// Service pour la logique métier liée aux véhicules du profil utilisateur
// Ce service utilise le modèle VehiculeProfil

require_once __DIR__ . '/../models/VehiculeProfil.php';

class VehiculeProfilService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new VehiculeProfil($pdo);
    }

    /**
     * Récupère la liste des véhicules d'un utilisateur
     */
    public function getVehiculesProfil($userId) {
        $vehicules = $this->model->getVehiculesByUser($userId);
        return [
            'success' => true,
            'vehicules' => $vehicules
        ];
    }
}
