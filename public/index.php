<?php
// Point d'entrée principal (front controller)
// - Charge bootstrap (session, helpers)
// - Charge la liste des routes
// - Instancie le routeur et traite la requête
// Pour l'instant, seules les URLs /api/... passent par ici.

require_once __DIR__ . '/../backend/includes/bootstrap.php';
$routes = require __DIR__ . '/../backend/routes/routes.php';
require_once __DIR__ . '/../backend/routes/Router.php';

$router = new Router(__DIR__ . '/../backend/controllers', $routes);
$router->handle();
