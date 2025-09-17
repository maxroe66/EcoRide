<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/UtilisateurService.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode invalide', 405); }

$raw = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$data = [];
if (stripos($contentType, 'application/json') !== false) {
  $tmp = json_decode($raw, true);
  if (is_array($tmp)) { $data = $tmp; }
} else {
  $data = $_POST;
}

eco_verify_csrf($data['csrf_token'] ?? null);
$email = trim($data['email'] ?? '');
$motDePasse = (string)($data['password'] ?? '');
if ($email === '' || $motDePasse === '') { eco_json_error('Champs manquants', 400); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { eco_json_error('Email invalide', 400); }
try {
  $pdo = Database::getMySQL();
  $service = new UtilisateurService($pdo);
  $result = $service->connexion($email, $motDePasse);
  if (!($result['success']??false)) { eco_json_error('Identifiants incorrects', 401); }
  $u = $result['user'];
  if (function_exists('session_regenerate_id')) { @session_regenerate_id(true); }
  $_SESSION['user_id'] = $u['utilisateur_id'];
  $_SESSION['user_nom'] = $u['nom'];
  $_SESSION['user_prenom'] = $u['prenom'];
  $_SESSION['user_credit'] = $u['credit'];
  $_SESSION['type_utilisateur'] = $u['type_utilisateur'] ?? null;
  eco_json_success(['message'=>'Connexion réussie']);
} catch (Throwable $e) {
  EcoLogger::log('auth_login_error', 'Exception: '.$e->getMessage().' trace='.substr($e->getTraceAsString(),0,300));
  eco_json_error('Erreur interne authentification', 500);
}
