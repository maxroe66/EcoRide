<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CovoiturageTerminerService.php';
if (!isset($_SESSION['user_id'])) { eco_json_error('Vous devez être connecté pour terminer un covoiturage.', 401); }
$user_id = (int)$_SESSION['user_id'];
try {
    $pdo = Database::getMySQL();
    $service = new CovoiturageTerminerService($pdo);
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true); if (!is_array($data)) { $data = []; }
    eco_verify_csrf($data['csrf_token'] ?? null);
    $covoiturage_id = isset($data['covoiturage_id']) ? (int)$data['covoiturage_id'] : 0;
    if ($covoiturage_id <= 0) { eco_json_error('ID du covoiturage manquant'); }
    $result = $service->terminer($user_id, $covoiturage_id);
    if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
    if ($result['success'] === false) { $msg = $result['message'] ?? 'Erreur terminaison.'; unset($result['success']); eco_json_error($msg, 400, $result); }
    eco_json_success($result);
} catch (Exception $e) { eco_json_error('Erreur lors de la terminaison du covoiturage', 500); }
