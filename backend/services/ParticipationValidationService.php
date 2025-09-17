<?php
// Service pour la logique métier liée à la validation d'une participation
require_once __DIR__ . '/../models/ParticipationValidation.php';
require_once __DIR__ . '/EscrowService.php';

class ParticipationValidationService {
    private $model;

    public function __construct($pdo) {
        $this->model = new ParticipationValidation($pdo);
    }

    /**
     * Valide la participation
     */
    public function valider($participation_id, $user_id) {
        $ok = $this->model->valider($participation_id, $user_id);
        if (!$ok) {
            return ['success' => false, 'message' => 'Erreur lors de la validation.'];
        }

        // Nouveau modèle: rien à créditer ici (fonds déjà bloqués via escrow hold)
        // La validation de participation ne libère pas les fonds. Libération après validation post-trajet (autre endpoint) ou incident.
        $pdo = $this->model->getPDO();
        $stmt = $pdo->prepare("SELECT participation_id FROM participation WHERE participation_id = ? AND utilisateur_id = ? LIMIT 1");
        $stmt->execute([$participation_id, $user_id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return ['success'=>true,'message'=>'Participation validée (enregistrement non trouvé pour escrow check).'];
        }
        $escrow = new EscrowService($pdo);
        $esc = $escrow->getByParticipation($participation_id);
        return [
            'success'=>true,
            'message'=>'Participation validée (fonds toujours bloqués).',
            'escrow_statut'=>$esc ? $esc['statut'] : 'absent'
        ];
    }
}
