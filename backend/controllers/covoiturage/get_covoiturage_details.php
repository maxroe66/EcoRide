<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CovoiturageDetailsService.php';
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { exit; }
$pdo = Database::getMySQL();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service = new CovoiturageDetailsService($pdo);
$result = $service->getDetails($id);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { $msg = $result['message'] ?? ($result['error'] ?? 'Erreur détails covoiturage'); eco_json_error($msg, 400); }
eco_json_success([
    'schema_version' => 1,
    'covoiturage' => $result['data']
]);
