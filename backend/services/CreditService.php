<?php
// Service centralisé pour opérations de crédit (débit/crédit + journalisation)
class CreditService {
    private PDO $pdo;
    private float $commissionFixe; // commission simple (peut être paramétrée)

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // Initialise depuis la constante si disponible
        $this->commissionFixe = defined('ECO_COMMISSION') ? (float)ECO_COMMISSION : 2.0;
    }

    public function setCommission(float $montant): void { $this->commissionFixe = max(0,$montant); }

    // Vérifie si un utilisateur a suffisamment de crédits
    public function hasSoldeSuffisant(int $utilisateurId, float $montant): bool {
        $stmt = $this->pdo->prepare('SELECT credit FROM utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$utilisateurId]);
        $solde = (float)$stmt->fetchColumn();
        return $solde >= $montant;
    }

    // Débit sécurisé : ne débite que si solde suffisant. Retourne success + solde_final
    public function debiter(int $utilisateurId, float $montant, string $raison = 'debit'): array {
        if ($montant <= 0) { return ['success'=>true,'message'=>'Montant nul']; }
        $this->pdo->prepare('UPDATE utilisateur SET credit = CASE WHEN credit >= ? THEN credit - ? ELSE credit END WHERE utilisateur_id = ?')
            ->execute([$montant, $montant, $utilisateurId]);
        $stmt = $this->pdo->prepare('SELECT credit FROM utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$utilisateurId]);
        $soldeApres = (float)$stmt->fetchColumn();
        if ($soldeApres < 0) { // garde-fou, ne devrait pas arriver
            $soldeApres = 0; }
        // Vérifier si effectivement débité
        if (!$this->hasSoldeSuffisant($utilisateurId, 0) && $soldeApres < $montant) {
            return ['success'=>false,'message'=>'Solde insuffisant'];
        }
    $this->logOperation($utilisateurId, 'debit', $montant, $raison);
        return ['success'=>true,'solde'=>$soldeApres];
    }

    // Crédit simple
    public function crediter(int $utilisateurId, float $montant, string $raison = 'credit'): array {
        if ($montant <= 0) { return ['success'=>true,'message'=>'Montant nul']; }
        $this->pdo->prepare('UPDATE utilisateur SET credit = credit + ? WHERE utilisateur_id = ?')
            ->execute([$montant, $utilisateurId]);
    $this->logOperation($utilisateurId, 'credit', $montant, $raison);
        return ['success'=>true];
    }

    // Transfert passager -> chauffeur avec commission plate-forme (commission retirée du montant total avant crédit chauffeur)
    public function transfererAvecCommission(int $passagerId, int $chauffeurId, float $montantPassager): array {
        if ($montantPassager <= 0) { return ['success'=>false,'message'=>'Montant invalide']; }
        $ownTxn = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $ownTxn = true;
        }
        try {
            $check = $this->hasSoldeSuffisant($passagerId, $montantPassager);
            if (!$check) { if ($ownTxn) { $this->pdo->rollBack(); } return ['success'=>false,'message'=>'Solde insuffisant passager']; }
            $deb = $this->debiter($passagerId, $montantPassager, 'transfer_with_commission');
            if (!$deb['success']) { if ($ownTxn) { $this->pdo->rollBack(); } return $deb; }
            $montantChauffeur = max(0, $montantPassager - $this->commissionFixe);
            $this->crediter($chauffeurId, $montantChauffeur, 'transfer_with_commission');
            if ($ownTxn && $this->pdo->inTransaction()) { $this->pdo->commit(); }
            return [
                'success'=>true,
                'montant_passager'=>$montantPassager,
                'montant_chauffeur'=>$montantChauffeur,
                'commission'=>$this->commissionFixe
            ];
        } catch (Exception $e) {
            if ($ownTxn && $this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            return ['success'=>false,'message'=>'Erreur transfert: '.$e->getMessage()];
        }
    }

    private function logOperation(int $utilisateurId, string $type, float $montant, string $raison = null): void {
        // Schéma actuel supposé : (utilisateur_id, type_operation, montant, date_operation auto) -> ignorer la raison si colonne inexistante
        $stmt = $this->pdo->prepare("INSERT INTO credit_operation (utilisateur_id, type_operation, montant) VALUES (?, ?, ?)");
        $stmt->execute([$utilisateurId, $type, $montant]);
    }
}
