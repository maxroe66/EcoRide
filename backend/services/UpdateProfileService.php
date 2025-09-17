<?php
// Service pour la logique métier liée à la mise à jour du profil utilisateur
require_once __DIR__ . '/../models/UpdateProfile.php';

class UpdateProfileService {
    private $model;

    public function __construct($pdo) {
        $this->model = new UpdateProfile($pdo);
    }

    /**
     * Met à jour le profil utilisateur
     */
    public function update($user_id, $data) {
        $ok = $this->model->update($user_id, $data);
        if ($ok) {
            return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
        } elseif ($ok === false) {
            return ['success' => false, 'message' => 'Aucune donnée à mettre à jour'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
        }
    }
}
