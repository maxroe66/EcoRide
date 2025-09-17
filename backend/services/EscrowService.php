<?php
require_once __DIR__ . '/CreditService.php';

class EscrowService {
    /*
     * Invariant métier: pour chaque participation il existe au plus UNE escrow_transaction.
     * Cycle valide: pending -> released OU pending -> refunded (mutuellement exclusif).
     * Aucune transition inverse n'est permise (idempotence protégée côté code).
     */
    private PDO $pdo;
    private CreditService $creditService;
    private float $commissionFixe; // aligné sur CreditService / constante globale

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    $this->creditService = new CreditService($pdo);
    $this->commissionFixe = defined('ECO_COMMISSION') ? (float)ECO_COMMISSION : 2.0;
    }

    public function creerHold(int $participationId, int $passagerId, int $chauffeurId, float $montant): array {
        if ($montant <= 0) return ['success'=>false,'message'=>'Montant invalide'];
        if (!$this->creditService->hasSoldeSuffisant($passagerId, $montant)) {
            return ['success'=>false,'message'=>'Solde insuffisant'];
        }
        $own = !$this->pdo->inTransaction();
        if ($own) $this->pdo->beginTransaction();
        try {
            // Débit passager (fonds bloqués de fait car crédit diminué)
            $deb = $this->creditService->debiter($passagerId, $montant, 'escrow_hold');
            if (!$deb['success']) { if ($own) $this->pdo->rollBack(); return $deb; }
            $stmt = $this->pdo->prepare('INSERT INTO escrow_transaction(participation_id, passager_id, chauffeur_id, montant_brut, commission, statut) VALUES (?,?,?,?,?,"pending")');
            $stmt->execute([$participationId, $passagerId, $chauffeurId, $montant, $this->commissionFixe]);
            if ($own) $this->pdo->commit();
            return ['success'=>true,'statut'=>'pending'];
        } catch(Exception $e) {
            if ($own && $this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success'=>false,'message'=>'Erreur hold: '.$e->getMessage()];
        }
    }

    public function release(int $participationId): array {
        $own = !$this->pdo->inTransaction();
        if ($own) $this->pdo->beginTransaction();
        try {
            $esc = $this->getByParticipation($participationId);
            if (!$esc) { if ($own) $this->pdo->rollBack(); return ['success'=>false,'message'=>'Escrow introuvable']; }
            if ($esc['statut'] === 'released') return ['success'=>true,'message'=>'Déjà libéré'];
            if ($esc['statut'] === 'refunded') return ['success'=>false,'message'=>'Déjà remboursé'];
            $net = max(0, $esc['montant_brut'] - $esc['commission']);
            $this->creditService->crediter((int)$esc['chauffeur_id'], (float)$net, 'escrow_release');
            $this->updateStatus($participationId, 'released');
            if ($own) $this->pdo->commit();
            return ['success'=>true,'montant_chauffeur'=>$net,'commission'=>$esc['commission']];
        } catch(Exception $e) {
            if ($own && $this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success'=>false,'message'=>'Erreur release: '.$e->getMessage()];
        }
    }

    public function refund(int $participationId): array {
        $own = !$this->pdo->inTransaction();
        if ($own) $this->pdo->beginTransaction();
        try {
            $esc = $this->getByParticipation($participationId);
            if (!$esc) { if ($own) $this->pdo->rollBack(); return ['success'=>false,'message'=>'Escrow introuvable']; }
            if ($esc['statut'] === 'released') return ['success'=>false,'message'=>'Déjà libéré'];
            if ($esc['statut'] === 'refunded') return ['success'=>true,'message'=>'Déjà remboursé'];
            $this->creditService->crediter((int)$esc['passager_id'], (float)$esc['montant_brut'], 'escrow_refund');
            $this->updateStatus($participationId, 'refunded');
            if ($own) $this->pdo->commit();
            return ['success'=>true,'montant_rembourse'=>$esc['montant_brut']];
        } catch(Exception $e) {
            if ($own && $this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success'=>false,'message'=>'Erreur refund: '.$e->getMessage()];
        }
    }

    public function getByParticipation(int $participationId) {
        $stmt = $this->pdo->prepare('SELECT * FROM escrow_transaction WHERE participation_id = ?');
        $stmt->execute([$participationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function updateStatus(int $participationId, string $statut): void {
        $stmt = $this->pdo->prepare('UPDATE escrow_transaction SET statut=? WHERE participation_id=?');
        $stmt->execute([$statut, $participationId]);
    }
}