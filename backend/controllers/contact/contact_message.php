<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/MailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { eco_json_error('Méthode invalide', 405); }
eco_verify_csrf($_POST['csrf_token'] ?? null);
$nom = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$sujetSelect = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
if ($nom === '' || $email === '' || $sujetSelect === '' || $message === '') { eco_json_error('Champs manquants.', 400); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { eco_json_error('Email invalide.', 400); }
if (strlen($message) < 10) { eco_json_error('Message trop court.', 400); }
$mapSujet = [
    'question' => 'Question générale',
    'probleme' => 'Problème technique',
    'suggestion' => 'Suggestion',
    'partenariat' => 'Partenariat',
    'autre' => 'Autre'
];
$sujetLisible = $mapSujet[$sujetSelect] ?? 'Contact';
$mailService = new MailService();
$configMail = AppConfig::mail();
$dest = $configMail['from_email'];
$body = '<html><body style="font-family:Arial;">'
      . '<h3>Nouveau message de contact</h3>'
      . '<p><strong>Nom :</strong> '.htmlspecialchars($nom).'</p>'
      . '<p><strong>Email :</strong> '.htmlspecialchars($email).'</p>'
      . '<p><strong>Catégorie :</strong> '.htmlspecialchars($sujetLisible).'</p>'
      . '<p><strong>Message :</strong><br>'.nl2br(htmlspecialchars($message)).'</p>'
      . '<hr><p style="font-size:12px;color:#666;">EcoRide - Formulaire de contact</p>'
      . '</body></html>';
if (!$mailService->sendMail($dest, 'EcoRide - Contact : '.$sujetLisible, $body)) {
    eco_json_error('Impossible d\'envoyer le message (mail).');
}
eco_json_success(['message' => 'Message envoyé', 'echo_email' => $email]);
