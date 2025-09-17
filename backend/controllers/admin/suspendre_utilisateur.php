<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AdminUtilisateurService.php';
eco_require_role(['administrateur']);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode non autorisée',405); }
$raw = file_get_contents('php://input');
$data = json_decode($raw, true); if (!is_array($data)) { eco_json_error('Corps JSON invalide.'); }
eco_verify_csrf($data['csrf_token'] ?? null);
$userId = isset($data['utilisateur_id']) ? (int)$data['utilisateur_id'] : 0;
$suspendre = isset($data['suspendre']) ? (bool)$data['suspendre'] : true;
$pdo = Database::getMySQL();
$service = new AdminUtilisateurService($pdo);
$result = $service->changerSuspension($userId, $suspendre);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur suspension', 400); }
eco_json_success($result);
