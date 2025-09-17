<?php
// Service pour la logique métier liée à la récupération des véhicules d'un utilisateur
// Ce service utilise le modèle Vehicule

require_once __DIR__ . '/../models/Vehicule.php';

class VehiculeUtilisateurService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new Vehicule($pdo);
    }

    /**
     * Récupère la liste des véhicules d'un utilisateur (id, marque, modèle, couleur, immatriculation)
     */
    public function getVehiculesUtilisateur($userId) {
        // On ajoute une méthode dédiée dans le modèle pour respecter l'encapsulation
        $vehicules = $this->model->getVehiculesUtilisateur($userId);
        return [
            'success' => true,
            'vehicules' => $vehicules
        ];
    }
}
