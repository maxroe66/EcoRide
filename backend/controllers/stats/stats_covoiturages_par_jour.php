<?php
// Statistiques : nombre de covoiturages par jour (admin)
// Regroupe les trajets par date de départ.
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
eco_require_role(['administrateur']);

try {
    $pdo = Database::getMySQL();
    // Retourne toutes les dates : le front filtrera l'affichage des jours futurs
    $sql = 'SELECT date_depart, COUNT(*) AS nb_covoiturages FROM covoiturage GROUP BY date_depart ORDER BY date_depart ASC';
    $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    eco_json_success(['stats' => $stats]);
} catch (Exception $e) {
    EcoLogger::log('stats', 'covoiturages_par_jour_error=' . $e->getMessage());
    eco_json_error('Erreur lors de la récupération des statistiques.', 500);
}
