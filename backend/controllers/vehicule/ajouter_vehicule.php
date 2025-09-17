<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/VehiculeService.php';
$pdo = Database::getMySQL();
if (!isset($_SESSION['user_id'])) { eco_json_error('Vous devez être connecté pour ajouter un véhicule.', 401); }
$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true); if (!is_array($data)) { $data = []; }
eco_verify_csrf($data['csrf_token'] ?? null);
$service = new VehiculeService($pdo);
$result = $service->ajouterVehicule($user_id, $data);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { $msg = $result['message'] ?? "Erreur lors de l'ajout."; unset($result['success']); eco_json_error($msg, 400, $result); }
eco_json_success($result);
