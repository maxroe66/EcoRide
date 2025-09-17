<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AvisMongoService.php';
require_once dirname(__DIR__, 2) . '/models/AvisFallback.php';

eco_require_login();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode invalide', 405); }
eco_verify_csrf($_POST['csrf_token'] ?? null);
$covoiturage_id = (int)($_POST['covoiturage_id'] ?? 0);
$note = (int)($_POST['note'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');
if ($covoiturage_id <= 0 || $note < 1 || $note > 5 || $commentaire === '') { eco_json_error('Paramètres avis invalides'); }
try {
    $avisCollection = Database::getMongoAvis();
    $service = new AvisMongoService($avisCollection);
    $existant = $avisCollection->findOne(['utilisateur_id'=>(int)$_SESSION['user_id'],'covoiturage_id'=>$covoiturage_id]);
    if ($existant) { eco_json_error('Avis déjà déposé'); }
    $dateCreation = (function(){ $class='MongoDB\\BSON\\UTCDateTime'; return class_exists($class)? new $class(): (new DateTime())->format(DateTime::ATOM); })();

    $avis = [
        'utilisateur_id'=>(int)$_SESSION['user_id'],
        'covoiturage_id'=>$covoiturage_id,
        'note'=>$note,
        'commentaire'=>$commentaire,
        'date_creation'=>$dateCreation,
        'statut'=>'pending'
    ];
    $result = $service->ajouterAvis($avis);
    if (!($result['success'] ?? false)) { eco_json_error($result['message'] ?? 'Échec avis'); }
    eco_json_success($result);
} catch (Exception $e) {
    try {
        $pdo = Database::getMySQL();
        $fallback = new AvisFallback($pdo);
        $ok = $fallback->inserer((int)$_SESSION['user_id'], $covoiturage_id, $note, $commentaire);
        if ($ok) { eco_json_success(['message' => 'Avis sauvegardé (fallback MySQL)']); }
        eco_json_error("Impossible d'enregistrer l'avis.");
    } catch (Exception $ex2) {
        eco_json_error('Erreur générale avis: '.$ex2->getMessage());
    }
}
