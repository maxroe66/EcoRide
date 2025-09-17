<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';

if (isset($_SESSION['user_id'])) {
    eco_json_success([
        'connected' => true,
        'user_id' => (int)$_SESSION['user_id'],
        'nom' => $_SESSION['user_nom'] ?? null,
        'prenom' => $_SESSION['user_prenom'] ?? null,
        'credit' => $_SESSION['user_credit'] ?? null,
        'type_utilisateur' => $_SESSION['type_utilisateur'] ?? null
    ]);
}
eco_json_success(['connected' => false]);
