<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/UtilisateurService.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode invalide', 405); }
eco_verify_csrf($_POST['csrf_token'] ?? null);
$nom = $_POST['nom'] ?? '';
$prenom = $_POST['prenom'] ?? '';
$email = $_POST['email'] ?? '';
$pseudo = trim($_POST['pseudo'] ?? '');
$password = $_POST['password'] ?? '';
$telephone = $_POST['telephone'] ?? '';
if ($pseudo === '' || !preg_match('/^[\p{L}0-9._-]{3,50}$/u', $pseudo)) {
    eco_json_error('Pseudo invalide (3-50 caractères, lettres, chiffres, ., -, _)');
}
$pdo = Database::getMySQL();
$service = new UtilisateurService($pdo);
$result = $service->inscription($nom, $prenom, $email, $password, $telephone, $pseudo);
if (!($result['success'] ?? false)) { eco_json_error($result['message'] ?? 'Échec inscription'); }
eco_json_success(['message' => 'Compte créé', 'user' => $result['user'] ?? null]);
