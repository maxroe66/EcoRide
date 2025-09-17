<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/VehiculeQueryService.php';

eco_require_login();
$user_id = (int)$_SESSION['user_id'];
$pdo = Database::getMySQL();
$service = new VehiculeQueryService($pdo);
$result = $service->listUtilisateur($user_id);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur véhicules', 400, $result); }
eco_json_success($result);
