<?php
// Retourne uniquement le crédit courant de l'utilisateur connecté
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CreditQueryService.php';
eco_require_login();
$user_id = (int)$_SESSION['user_id'];
$pdo = Database::getMySQL();
$service = new CreditQueryService($pdo);
$result = $service->getCredit($user_id);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur crédit', $result['code'] ?? 400); }
eco_json_success(['credit'=>$result['credit']]);
