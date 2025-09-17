<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
// Renvoie le jeton CSRF courant (endpoint sécurité)
eco_json_success(['csrf_token' => eco_csrf_token()]);
