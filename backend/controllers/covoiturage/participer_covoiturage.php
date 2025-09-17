<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/ParticipationCovoiturageService.php';

eco_require_login();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode invalide', 405); }
// Accept both form and JSON payload
$raw = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$payload = $_POST;
if (stripos($contentType, 'application/json') !== false) {
    $tmp = json_decode($raw, true);
    if (is_array($tmp)) { $payload = $tmp; }
}
eco_verify_csrf($payload['csrf_token'] ?? null);
$user_id = (int)$_SESSION['user_id'];
$covoiturage_id = $payload['covoiturage_id'] ?? null;
$nb_places = (int)($payload['nb_places'] ?? 1);
if (!$covoiturage_id) { eco_json_error('covoiturage_id requis'); }
$pdo = Database::getMySQL();
$service = new ParticipationCovoiturageService($pdo);
$result = $service->participer($user_id, $covoiturage_id, $nb_places);
if (!($result['success'] ?? false)) { eco_json_error($result['message'] ?? 'Échec participation'); }
if (isset($result['credit_apres'])) {
    $_SESSION['user_credit'] = (float)$result['credit_apres'];
}
eco_json_success($result);
