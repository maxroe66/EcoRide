<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/HistoriqueCovoiturageService.php';

eco_require_login();
$user_id = (int)$_SESSION['user_id'];
$pdo = Database::getMySQL();
$service = new HistoriqueCovoiturageService($pdo);
$result = $service->getHistorique($user_id);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur historique', 400, $result); }
eco_json_success($result);
