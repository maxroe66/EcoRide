<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/ParticipationEvaluationService.php';

if (!isset($_SESSION['user_id'])) { eco_json_error('Vous devez être connecté.', 401); }
$user_id = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) { $data = []; }
eco_verify_csrf($data['csrf_token'] ?? null);
$participation_id = isset($data['participation_id']) ? (int)$data['participation_id'] : 0;
$validation_type = $data['validation_type'] ?? null;
$note = isset($data['note']) ? (int)$data['note'] : null;
$commentaire = isset($data['commentaire']) ? trim($data['commentaire']) : '';
$probleme = isset($data['probleme']) ? trim($data['probleme']) : '';
if ($participation_id <= 0) { eco_json_error('ID de participation manquant'); }
$pdo = Database::getMySQL();
$eval = new ParticipationEvaluationService($pdo);
if ($validation_type === 'mal') {
    $res = $eval->validerProbleme($participation_id, $user_id, $probleme, $note);
    if (!($res['success'] ?? false)) { eco_json_error($res['message'] ?? 'Erreur signalement', ($res['code'] ?? 400)); }
    eco_json_success($res);
}
// par défaut : validation type "bien" (ou neutre)
$res = $eval->validerBien($participation_id, $user_id, $note, $commentaire);
if (!($res['success'] ?? false)) { eco_json_error($res['message'] ?? 'Erreur validation'); }
eco_json_success($res);
