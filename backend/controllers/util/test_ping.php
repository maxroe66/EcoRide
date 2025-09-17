<?php
// Contrôleur de test simple pour vérifier le routeur
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
eco_json_success(['message' => 'pong', 'time' => date('c')]);
