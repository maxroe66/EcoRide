<?php
// Service legacy (déprécié) pour la recherche via l'ancien contrôleur traitement.php
// Conserver tant que traitement.php est utilisé par le front historique.
// Nouveau code doit utiliser RechercheCovoiturageService.
require_once __DIR__ . '/../models/RechercheCovoitTraitement.php';

class RechercheCovoitTraitementService {
    private $model;
    public function __construct($pdo) { $this->model = new RechercheCovoitTraitement($pdo); }

    public function rechercher($lieu_depart, $lieu_arrivee, $date_depart, $prix_max, $ecologique) {
        return $this->model->rechercher($lieu_depart, $lieu_arrivee, $date_depart, $prix_max, $ecologique);
    }
}
