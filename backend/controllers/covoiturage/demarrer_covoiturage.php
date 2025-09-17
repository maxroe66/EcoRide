<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CovoiturageStatutService.php';
$pdo = Database::getMySQL();
if (!isset($_SESSION['user_id'])) { eco_json_error('Vous devez être connecté pour démarrer un covoiturage.', 401); }
$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = []; }
eco_verify_csrf($data['csrf_token'] ?? null);
$service = new CovoiturageStatutService($pdo);
$result = $service->demarrer($user_id, $data);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { $msg = $result['message'] ?? 'Erreur démarrage.'; unset($result['success']); eco_json_error($msg, 400, $result); }
eco_json_success($result);
