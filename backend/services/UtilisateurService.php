<?php
// Service pour la logique métier liée aux utilisateurs (inscription, connexion, etc.)
require_once __DIR__ . '/../models/Utilisateur.php';

class UtilisateurService {
    private $model;

    public function __construct($pdo) {
        $this->model = new Utilisateur($pdo);
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function inscription($nom, $prenom, $email, $motDePasse, $telephone, $pseudo) {
        $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);
        $resultat = $this->model->inserer($nom, $prenom, $email, $motDePasseHash, $telephone, $pseudo);
        if ($resultat['success']??false) {
            return ['success' => true, 'message' => 'Compte créé avec succès'];
        }
        if (!empty($resultat['duplicate'])) {
            return ['success'=>false,'message'=>'Email ou pseudo déjà utilisé'];
        }
        return ['success'=>false,'message'=>$resultat['message'] ?? 'Erreur lors de la création'];
    }

    /**
     * Connexion d'un utilisateur
     */
    public function connexion($email, $motDePasse) {
        $utilisateur = $this->model->getByEmail($email);
        if ($utilisateur && password_verify($motDePasse, $utilisateur['password'])) {
            return [
                'success' => true,
                'user' => $utilisateur
            ];
        } else {
            return ['success' => false, 'message' => 'Identifiants incorrects'];
        }
    }
}
