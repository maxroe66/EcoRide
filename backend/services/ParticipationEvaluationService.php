<?php
// Service d'évaluation / clôture de participation (validation positive ou problème)
require_once __DIR__ . '/../models/Incident.php';
require_once __DIR__ . '/ParticipationValidationService.php';
require_once __DIR__ . '/AvisMongoService.php'; // conservé pour compat éventuelle
require_once __DIR__ . '/AvisFacadeService.php';
require_once __DIR__ . '/IncidentService.php';
require_once __DIR__ . '/EscrowService.php';

class ParticipationEvaluationService {
    private $pdo;
    private $validationService;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->validationService = new ParticipationValidationService($pdo);
    }

    /**
     * Traite une validation avec type 'bien' (optionnellement note + commentaire => avis)
     */
    public function validerBien(int $participation_id, int $user_id, ?int $note, string $commentaire): array {
        $res = $this->validationService->valider($participation_id, $user_id);
        if (!($res['success'] ?? false)) return $res;

        // Création avis si fournis
        if ($note !== null || $commentaire !== '') {
            try {
                $stmt = $this->pdo->prepare('SELECT covoiturage_id FROM participation WHERE participation_id = ? AND utilisateur_id = ?');
                $stmt->execute([$participation_id, $user_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $facade = new AvisFacadeService($this->pdo);
                    $facade->ajouterAvisPending($user_id, (int)$row['covoiturage_id'], $note, $commentaire);
                    $res['avis'] = 'Avis enregistré (pending).';
                }
            } catch (Exception $e) { $res['avis_error'] = "Impossible d'enregistrer l'avis: " . $e->getMessage(); }
        }
        return $res;
    }

    /**
     * Traite une validation avec type 'mal' => incident + avis incident.
     */
    public function validerProbleme(int $participation_id, int $user_id, string $probleme, ?int $note): array {
        if (trim($probleme) === '') {
            return ['success'=>false,'message'=>'Veuillez décrire le problème.'];
        }
        try {
            $stmt = $this->pdo->prepare('SELECT covoiturage_id FROM participation WHERE participation_id = ? AND utilisateur_id = ? LIMIT 1');
            $stmt->execute([$participation_id, $user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { return ['success'=>false,'message'=>'Participation introuvable.']; }
            $covoiturage_id = (int)$row['covoiturage_id'];
            $incidentService = new IncidentService($this->pdo);
            $resInc = $incidentService->creerPourParticipation($participation_id, $covoiturage_id, $user_id, $probleme);
            if (!($resInc['success'] ?? false)) { return $resInc; }
            $incident_id = $resInc['incident_id'];
            // Avis lié à incident si pas déjà existant
            try { $facade = new AvisFacadeService($this->pdo); $facade->ajouterAvisPending($user_id, $covoiturage_id, $note !== null? (int)$note : null, $probleme, ['incident'=>true,'incident_id'=>$incident_id]); }
            catch (Exception $e) { EcoLogger::log('avis_debug', 'avis_incident_error=' . $e->getMessage()); }
            return ['success'=>true,'message'=>"Signalement enregistré (avis associé). Un employé examinera le trajet. Aucun crédit versé pour l'instant."];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            EcoLogger::log('incident_debug', "erreur_signalement participation_id=$participation_id user_id=$user_id msg=" . $e->getMessage());
            return ['success'=>false,'message'=>"Erreur lors de l'enregistrement du problème (voir logs).", 'code'=>500];
        }
    }

    /**
     * Confirme le trajet (post-trajet) en libérant éventuellement les fonds ou en créant un incident.
     * Regroupe l'ancienne logique du controller confirmer_trajet.php pour garder les contrôleurs minces.
     * $payload attend: ['ok'=>bool, 'probleme'=>string, 'note'=>?int, 'commentaire'=>string]
     */
    public function confirmerTrajet(int $participation_id, int $user_id, array $payload): array {
        $ok = (bool)($payload['ok'] ?? false);
        $probleme = trim($payload['probleme'] ?? '');
        $note = isset($payload['note']) ? (int)$payload['note'] : null;
        $commentaire = trim($payload['commentaire'] ?? '');

        if ($participation_id <= 0) {
            return ['success'=>false,'message'=>'Participation invalide'];
        }

        try {
            $pdo = $this->pdo; // plus lisible
            // Récupération participation + chauffeur
            $stmt = $pdo->prepare('SELECT p.covoiturage_id, p.utilisateur_id, p.statut, c.conducteur_id as chauffeur_id FROM participation p JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id WHERE p.participation_id = ? LIMIT 1');
            $stmt->execute([$participation_id]);
            $part = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$part || (int)$part['utilisateur_id'] !== $user_id) {
                return ['success'=>false,'message'=>'Participation non trouvée', 'code'=>404];
            }

            // Déjà traitée ? (idempotence)
            if (in_array($part['statut'], ['validee','probleme'], true)) {
                return ['success'=>true,'message'=>'Participation déjà traitée.','statut'=>$part['statut']];
            }

            if ($ok) {
                // Libération des fonds escrow
                $escrow = new EscrowService($pdo);
                $rel = $escrow->release($participation_id);
                if (!($rel['success'] ?? false)) {
                    return ['success'=>false,'message'=>'Libération impossible: '.($rel['message'] ?? 'erreur'), 'code'=>409];
                }
                // Mise à jour statut
                try {
                    $upStmt = $pdo->prepare("UPDATE participation SET statut='validee' WHERE participation_id = ? AND utilisateur_id = ?");
                    $upStmt->execute([$participation_id, $user_id]);
                } catch (Exception $eUp) {
                    EcoLogger::log('participation', 'err_update_statut participation_id='.$participation_id.' msg='.$eUp->getMessage());
                }
                // Création avis éventuel (réutilisation de validerBien pour factoriser la création d'avis si note/commentaire fournis)
                $avisPendingId = null;
                if ($note !== null || $commentaire !== '') {
                    try { $facade = new AvisFacadeService($pdo); $resAvis = $facade->ajouterAvisPending($user_id, (int)$part['covoiturage_id'], $note, $commentaire); $avisPendingId = $resAvis['inserted_id'] ?? null; }
                    catch(Exception $e) { EcoLogger::log('avis', 'err_add_avis='.$e->getMessage()); }
                }
                return [
                    'success'=>true,
                    'message'=>'Trajet confirmé, fonds versés au chauffeur.',
                    'release'=>$rel,
                    'statut'=>'validee',
                    'avis_pending_id'=>$avisPendingId
                ];
            } else {
                if ($probleme === '') {
                    return ['success'=>false,'message'=>'Veuillez décrire le problème.'];
                }
                $incidentService = new IncidentService($pdo);
                $resInc = $incidentService->creerPourParticipation($participation_id, (int)$part['covoiturage_id'], $user_id, $probleme);
                if (!($resInc['success'] ?? false)) { return ['success'=>false,'message'=>'Erreur incident (rollback).']; }
                $incident_id = $resInc['incident_id'];
                $avisPendingId = null;
                try { $facade = new AvisFacadeService($pdo); $resAvis = $facade->ajouterAvisPending($user_id, (int)$part['covoiturage_id'], $note, $probleme, ['incident'=>true,'incident_id'=>$incident_id]); $avisPendingId = $resAvis['inserted_id'] ?? null; }
                catch(Exception $e) { EcoLogger::log('avis', 'err_add_incident_avis='.$e->getMessage()); }
                return [
                    'success'=>true,
                    'message'=>'Incident déclaré, fonds toujours bloqués.',
                    'incident_id'=>$incident_id,
                    'avis_pending_id'=>$avisPendingId
                ];
            }
        } catch(Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
            return ['success'=>false,'message'=>'Erreur confirmation trajet', 'code'=>500];
        }
    }
}
