<?php
// Garde d'accès : empêche l'appel direct aux contrôleurs hors routeur
if (!defined('ECO_VIA_ROUTER')) {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'message' => 'Accès direct interdit']);
    exit;
}
