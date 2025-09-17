<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { eco_json_error('Méthode invalide', 405); }
session_destroy();
eco_json_success(['message' => 'Déconnexion réussie']);
