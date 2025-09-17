<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/models/Incident.php';
require_once dirname(__DIR__, 2) . '/services/EscrowService.php';
require_once dirname(__DIR__, 2) . '/services/dto/IncidentDtoBuilder.php';

eco_require_role(['employe','administrateur']);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) { eco_json_error('Payload JSON invalide'); }
eco_verify_csrf($data['csrf_token'] ?? null);
$incident_id = isset($data['incident_id']) ? (int)$data['incident_id'] : 0;
if (!$incident_id) { eco_json_error('ID manquant', 400); }
try {
    $pdo = Database::getMySQL();
    $incidentModel = new Incident($pdo);
    if (method_exists($incidentModel, 'ensureTablePublic')) { $incidentModel->ensureTablePublic(); }
    $inc = $incidentModel->get($incident_id);
    if (!$inc) { eco_json_error('Incident introuvable', 404); }
    $stmt = $pdo->prepare('SELECT p.participation_id FROM participation p WHERE p.participation_id = ? LIMIT 1');
    $stmt->execute([$inc['participation_id']]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) { eco_json_error('Participation liée introuvable', 404); }
    EcoLogger::log('incident', 'resolve_pre incident_id=' . $incident_id . ' participation_id=' . $inc['participation_id']);
    $escrow = new EscrowService($pdo);
    $mode = $_POST['mode'] ?? ($_GET['mode'] ?? 'release');
    $pdo->beginTransaction();
    $incidentModel->marquerResolue($incident_id);
    $pdo->prepare("UPDATE participation SET statut='validee' WHERE participation_id = ? AND statut='probleme'")
        ->execute([$inc['participation_id']]);
    $res = ($mode === 'refund') ? $escrow->refund((int)$inc['participation_id']) : $escrow->release((int)$inc['participation_id']);
    if (!$res['success']) { $pdo->rollBack(); eco_json_error('Echec résolution: '.$res['message'], 409); }
    $pdo->commit();
    EcoLogger::log('incident', 'resolve_post incident_id=' . $incident_id . ' mode='.$mode);

    // Reconstruire DTO enrichi (post-résolution). Requêtes minimales (on réutilise $inc partiellement, statut changé en DB) :
    $incFresh = $incidentModel->get($incident_id) ?: $inc; // devrait refléter statut résolu
    // Récup données associées pour builder
    $stmtCov = $pdo->prepare("SELECT c.lieu_depart,c.lieu_arrivee,c.date_depart,c.heure_depart,c.heure_arrivee,c.conducteur_id,c.covoiturage_id FROM covoiturage c WHERE c.covoiturage_id = ? LIMIT 1");
    $stmtCov->execute([$incFresh['covoiturage_id']]);
    $cov = $stmtCov->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmtPart = $pdo->prepare("SELECT p.participation_id,p.utilisateur_id,p.nb_places,e.statut AS escrow_statut,e.montant_brut,e.commission FROM participation p LEFT JOIN escrow_transaction e ON e.participation_id=p.participation_id WHERE p.participation_id=? LIMIT 1");
    $stmtPart->execute([$incFresh['participation_id']]);
    $part = $stmtPart->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmtPass = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1");
    $stmtPass->execute([$incFresh['utilisateur_id']]);
    $passager = $stmtPass->fetch(PDO::FETCH_ASSOC) ?: [];
    $chauffeur = [];
    if (!empty($cov['conducteur_id'])) { $stmtCh = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1"); $stmtCh->execute([$cov['conducteur_id']]); $chauffeur = $stmtCh->fetch(PDO::FETCH_ASSOC) ?: []; }
    $builder = new IncidentDtoBuilder();
    $dto = $builder->build($incFresh, $cov, $part, $passager, $chauffeur, $mode);
    // Normalisation réponse v2
    eco_json_success([
        'schema_version' => 2,
        'message' => 'Incident résolu',
        'mode' => $mode,
        'details' => $res,
        'incident' => $dto,
        'resolution' => [
            'mode' => $mode,
            'at' => date('c'),
        ],
        // Compat v1 champs
        'status' => 'resolu'
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    EcoLogger::log('incident', 'resolve_error incident_id=' . $incident_id . ' msg=' . $e->getMessage());
    eco_json_error('Erreur résolution incident', 500);
}
