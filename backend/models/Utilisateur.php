<?php
// Modèle pour la gestion des utilisateurs (inscription, connexion, etc.)

class Utilisateur {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function emailExiste($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Insère un nouvel utilisateur
     */
    public function inserer($nom, $prenom, $email, $motDePasseHash, $telephone, $pseudo) {
        // Crédit initial défini par US7 : 20 crédits à la création de compte
        $creditInitial = defined('ECO_CREDIT_INITIAL') ? (float)ECO_CREDIT_INITIAL : 20.00;
        try {
            $stmt = $this->pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, password, telephone, credit, pseudo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $email, $motDePasseHash, $telephone, $creditInitial, $pseudo]);
            return ['success'=>true];
        } catch (PDOException $e) {
            @file_put_contents(__DIR__.'/../logs/inscription_debug.log', date('c')." PDO_ERROR code=".$e->getCode()." sqlstate=".($e->errorInfo[0]??'')." driverCode=".($e->errorInfo[1]??'')." msg=".$e->getMessage()."\n", FILE_APPEND);
            if ($e->getCode() === '23000') {
                $msg = 'Email ou pseudo déjà utilisé';
                return ['success'=>false,'duplicate'=>true,'message'=>$msg];
            }
            return ['success'=>false,'message'=>'Erreur insertion utilisateur'];
        }
    }

    /**
     * Récupère un utilisateur par email
     */
    public function getByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
