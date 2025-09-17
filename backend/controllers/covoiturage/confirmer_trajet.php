<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/services/ParticipationEvaluationService.php';

eco_require_login();
$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?: [];
eco_verify_csrf($data['csrf_token'] ?? null);
$participation_id = isset($data['participation_id']) ? (int)$data['participation_id'] : 0;
if ($participation_id <= 0) { eco_json_error('Participation invalide'); }
$service = new ParticipationEvaluationService(Database::getMySQL());
$result = $service->confirmerTrajet($participation_id, $user_id, $data);
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur confirmation traj.', $result['code'] ?? 400, $result); }
eco_json_success($result);
