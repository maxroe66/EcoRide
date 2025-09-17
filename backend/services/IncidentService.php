<?php
// Service de gestion des incidents liés aux participations / covoiturages
require_once __DIR__ . '/../models/Incident.php';

class IncidentService {
    private PDO $pdo;
    private Incident $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->model = new Incident($pdo);
    }

    /**
     * Crée un incident pour une participation et marque la participation en 'probleme'.
     * Retourne success + incident_id
     */
    public function creerPourParticipation(int $participationId, int $covoiturageId, int $userId, string $description): array {
        $description = trim($description);
        if ($participationId <= 0 || $covoiturageId <= 0 || $userId <= 0 || $description === '') {
            return ['success'=>false,'message'=>'Paramètres incident invalides'];
        }
        try {
            if (method_exists($this->model,'ensureTablePublic')) { $this->model->ensureTablePublic(); }
            $ownTxn = false;
            if (!$this->pdo->inTransaction()) { $this->pdo->beginTransaction(); $ownTxn = true; }
            // Marquer participation
            $up = $this->pdo->prepare("UPDATE participation SET statut='probleme' WHERE participation_id = ?");
            $up->execute([$participationId]);
            // Créer incident
            $incidentId = $this->model->creer($participationId, $covoiturageId, $userId, $description);
            if ($ownTxn && $this->pdo->inTransaction()) { $this->pdo->commit(); }
            return ['success'=>true,'incident_id'=>$incidentId];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            EcoLogger::log('incident', 'creer_participation_error participation_id='.$participationId.' msg='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur création incident'];
        }
    }
}
