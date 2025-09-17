<?php
// Statistiques : crédits plateforme par jour (admin)
// Hypothèse : 2 crédits prélevés à la création (simplification MVP)
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
eco_require_role(['administrateur']);

try {
    $pdo = Database::getMySQL();
    $sql = "SELECT date_depart, COUNT(*) * 2 AS credits_gagnes FROM covoiturage WHERE statut = 'termine' GROUP BY date_depart ORDER BY date_depart ASC";
    $stats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $total = array_reduce($stats, fn($c,$r)=> $c + (int)$r['credits_gagnes'], 0);
    eco_json_success(['stats' => $stats, 'total_credits' => $total]);
} catch (Exception $e) {
    EcoLogger::log('stats', 'credits_plateforme_error=' . $e->getMessage());
    eco_json_error('Erreur lors de la récupération des crédits.', 500);
}
