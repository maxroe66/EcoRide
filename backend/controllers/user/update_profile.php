<?php
// Mise à jour profil utilisateur refactor helpers
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/UpdateProfileService.php';
eco_require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    eco_json_error('Méthode non autorisée', 405);
}

$pdo = Database::getMySQL();
$donnees = json_decode(file_get_contents('php://input'), true);
if (!$donnees) {
    eco_json_error('Données invalides');
}
eco_verify_csrf($donnees['csrf_token'] ?? null);
$service = new UpdateProfileService($pdo);
$user_id = (int)$_SESSION['user_id'];
$result = $service->update($user_id, $donnees);
if (($result['success'] ?? false) !== true) {
    eco_json_error($result['message'] ?? 'Échec mise à jour', 400);
}
eco_json_success($result);
