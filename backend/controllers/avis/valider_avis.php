<?php
file_put_contents(dirname(__DIR__,2).'/logs/avis_debug.log', date('c') . " | valider_avis.php called raw=".file_get_contents('php://input').PHP_EOL, FILE_APPEND);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AvisMongoService.php';
require_once dirname(__DIR__, 2) . '/services/AvisFacadeService.php';
require_once dirname(__DIR__, 2) . '/models/Incident.php';

eco_require_role(['employe','administrateur']);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) { eco_json_error('Payload JSON invalide'); }
eco_verify_csrf($data['csrf_token'] ?? null);
$avisId = $data['avis_id'] ?? null;
$action = $data['action'] ?? null;
EcoLogger::log('avis', 'validation_input avis_id=' . ($avisId ?? 'null') . ' action=' . ($action ?? 'null'));
if (!$avisId || !in_array($action, ['valider','refuser'], true)) { eco_json_error('Paramètres manquants ou invalides'); }
try {
    $avisCollection = Database::getMongoAvis();
    $service = new AvisMongoService($avisCollection);
    $avisDocument = null;
    try { $oidClass = 'MongoDB\\BSON\\ObjectId'; if (class_exists($oidClass)) { $mongoId = new $oidClass($avisId); $avisDocument = $avisCollection->findOne(['_id' => $mongoId]); } else { $avisDocument = $avisCollection->findOne(['_id' => $avisId]); } } catch (Exception $ie) { EcoLogger::log('avis', 'objectid_error avis_id=' . $avisId . ' msg=' . $ie->getMessage()); }
    $ok = false;
    $newStatus = null;
    $replicatedFallback = false;
    $driverNewAverage = null;
    $driverCount = null;

    if ($action === 'valider') {
        $ok = $service->validerAvis($avisId);
        $newStatus = $ok ? 'approved' : null;
        if ($ok && $avisDocument) {
            // Réplication fallback + calcul stats conducteur
            try {
                $facade = new AvisFacadeService(Database::getMySQL());
                $beforeStats = null;
                // On peut calculer stats après insertion éventuelle
                $facade->replicationFallbackSiValide($avisDocument);
                $replicatedFallback = true; // si pas d'exception on considère fait (même si doublon ignoré)
                // Calcul moyenne / count fallback pour le conducteur (si on peut déduire l'id conducteur via covoiturage)
                $covoitId = (int)($avisDocument['covoiturage_id'] ?? 0);
                if ($covoitId > 0) {
                    try {
                        $pdo = Database::getMySQL();
                        $stmtCh = $pdo->prepare('SELECT conducteur_id FROM covoiturage WHERE covoiturage_id=? LIMIT 1');
                        $stmtCh->execute([$covoitId]);
                        $chId = (int)($stmtCh->fetchColumn());
                        if ($chId > 0) {
                            // nouvelle moyenne & count
                            $stmtAvg = $pdo->prepare('SELECT ROUND(AVG(note),1) AS avg_note, COUNT(*) AS cnt FROM avis_fallback af JOIN covoiturage c ON af.covoiturage_id=c.covoiturage_id WHERE c.conducteur_id=? AND af.note BETWEEN 1 AND 5');
                            $stmtAvg->execute([$chId]);
                            $rowStats = $stmtAvg->fetch(PDO::FETCH_ASSOC) ?: [];
                            $driverNewAverage = isset($rowStats['avg_note']) ? (float)$rowStats['avg_note'] : null;
                            $driverCount = isset($rowStats['cnt']) ? (int)$rowStats['cnt'] : null;
                        }
                    } catch (Exception $es) { EcoLogger::log('avis_stats','err='.$es->getMessage()); }
                }
            } catch (Exception $e2) { EcoLogger::log('avis_replication','err='.$e2->getMessage()); }
        }
    } else {
        $ok = $service->refuserAvis($avisId);
        $newStatus = $ok ? 'refused' : null;
    }

    eco_json_success([
        'schema_version' => 2,
        'status' => $newStatus, // normalisé (alias de new_status v1)
        'operation_success' => $ok,
        'replicated_fallback' => $replicatedFallback,
        'driver_ratings' => [ // renommé ratings -> driver_ratings
            'average' => $driverNewAverage,
            'count' => $driverCount,
        ],
        // Pour compat front legacy éventuel
        'new_status' => $newStatus,
        'ratings' => [
            'driver_new_average' => $driverNewAverage,
            'driver_count' => $driverCount,
        ]
    ]);
} catch (Exception $e) {
    EcoLogger::log('avis', 'valider_avis_error msg=' . $e->getMessage());
    eco_json_error("Erreur lors de la mise à jour de l'avis", 500);
}
