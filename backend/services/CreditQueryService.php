<?php
// Service de consultation du crédit utilisateur (lecture seule)
class CreditQueryService {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function getCredit(int $userId): array {
        try {
            $stmt = $this->pdo->prepare('SELECT credit FROM utilisateur WHERE utilisateur_id = ?');
            $stmt->execute([$userId]);
            $credit = $stmt->fetchColumn();
            if ($credit === false) { return ['success'=>false,'message'=>'Utilisateur introuvable','code'=>404]; }
            return ['success'=>true,'credit'=>(float)$credit];
        } catch (Exception $e) {
            EcoLogger::log('credit','get_error user_id='.$userId.' msg='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur récupération crédit','code'=>500];
        }
    }
}
