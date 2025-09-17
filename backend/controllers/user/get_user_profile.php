<?php
// Récupérer le profil complet de l'utilisateur connecté
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/UserProfileService.php';

eco_require_login();
$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = Database::getMySQL();
    $service = new UserProfileService($pdo);
    $result = $service->getProfile($user_id);
    if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
    if ($result['success'] === false) {
        $msg = $result['message'] ?? 'Erreur profil';
        eco_json_error($msg, 400);
    }
    $payload = ['user' => $result['user']];
    eco_json_success($payload);
} catch (Exception $e) {
    EcoLogger::log('profil', 'get_profile_error=' . $e->getMessage());
    eco_json_error('Erreur serveur: ' . $e->getMessage(), 500);
}
