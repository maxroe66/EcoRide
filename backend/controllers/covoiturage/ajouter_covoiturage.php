<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CovoiturageService.php';

$pdo = Database::getMySQL();
if (!isset($_SESSION['user_id'])) { eco_json_error('Vous devez être connecté pour ajouter un covoiturage.', 401); }
$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = []; }
eco_verify_csrf($data['csrf_token'] ?? null);
$service = new CovoiturageService($pdo);
$result = $service->ajouterCovoiturage($user_id, $data);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) {
    $message = $result['message'] ?? "Erreur lors de l'ajout.";
    unset($result['success']);
    $extra = $result;
    eco_json_error($message, 400, $extra);
}
eco_json_success($result);
