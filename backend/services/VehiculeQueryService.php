<?php
// Service unifié pour récupération des véhicules d'un utilisateur (profil ou liste simple)
// Fusion de VehiculeUtilisateurService et VehiculeProfilService (réduction duplication)

require_once __DIR__ . '/../models/Vehicule.php';
require_once __DIR__ . '/../models/VehiculeProfil.php';

class VehiculeQueryService {
    private Vehicule $vehiculeModel;
    private VehiculeProfil $vehiculeProfilModel;

    public function __construct($pdo) {
        $this->vehiculeModel = new Vehicule($pdo);
        $this->vehiculeProfilModel = new VehiculeProfil($pdo);
    }

    // Liste courte (id, marque, modèle, couleur, immatriculation)
    public function listUtilisateur($userId): array {
        $vehicules = $this->vehiculeModel->getVehiculesUtilisateur($userId);
        return ['success'=>true,'vehicules'=>$vehicules];
    }

    // Détails profil (attributs étendus)
    public function listProfil($userId): array {
        $vehicules = $this->vehiculeProfilModel->getVehiculesByUser($userId);
        return ['success'=>true,'vehicules'=>$vehicules];
    }
}
