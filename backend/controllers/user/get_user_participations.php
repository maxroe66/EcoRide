<?php
// Participations de l'utilisateur connecté
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/ParticipationService.php';

eco_require_login();
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = Database::getMySQL();
    $service = new ParticipationService($pdo);
    $result = $service->getUserParticipations($user_id);
    if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
    if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur participations', 400, $result); }
    eco_json_success($result);
} catch (Exception $e) {
    EcoLogger::log('participations', 'get_error=' . $e->getMessage());
    eco_json_error('Erreur serveur: ' . $e->getMessage(), 500);
}
