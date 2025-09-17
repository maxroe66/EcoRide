<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/RechercheCovoiturageService.php';

// CORS / pré-vol simple (MVP)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { exit; }

try {
    $pdo = Database::getMySQL();
    $service = new RechercheCovoiturageService($pdo);
    // Extraction & normalisation critères
    $criteres = [
        'lieu_depart'      => $_GET['lieu_depart']     ?? null,
        'lieu_arrivee'     => $_GET['lieu_arrivee']    ?? null,
        'date_depart'      => null,
        'date_from'        => $_GET['date_from']       ?? null,
        'date_to'          => $_GET['date_to']         ?? null,
        'nb_places_min'    => isset($_GET['nb_places']) ? (int)$_GET['nb_places'] : 1,
        'prix_max'         => isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : null,
        'ecologique_only'  => isset($_GET['ecologique']) && $_GET['ecologique'] === 'true',
        'page'             => isset($_GET['page']) ? (int)$_GET['page'] : 1,
        'per_page'         => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20,
    'partial_match'    => isset($_GET['partial']) && $_GET['partial'] === 'true',
    'min_rating'       => isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : null,
    'max_duration'     => isset($_GET['max_duration']) ? (int)$_GET['max_duration'] : null
    ];
    if (isset($_GET['date_depart'])) {
        $date_depart = trim($_GET['date_depart']);
        if (preg_match('/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/', $date_depart, $m)) {
            $criteres['date_depart'] = $m[3] . '-' . $m[2] . '-' . $m[1];
        } else {
            $criteres['date_depart'] = $date_depart;
        }
    }
    $response = $service->rechercherAvecSuggestion($criteres); // Ajoute éventuellement suggestion
    eco_json_success($response);
} catch (Exception $e) {
    EcoLogger::log('search', 'rechercher_error=' . $e->getMessage());
    eco_json_error('Erreur lors de la recherche', 500);
}
