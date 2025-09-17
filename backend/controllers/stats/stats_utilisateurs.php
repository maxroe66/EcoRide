<?php
// Statistiques globales utilisateurs (admin)
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
eco_require_role(['administrateur']);

try {
    $pdo = Database::getMySQL();
    $total = (int)$pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
    $employes = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur WHERE type_utilisateur = 'employe'")->fetchColumn();
    $admins = (int)$pdo->query("SELECT COUNT(*) FROM utilisateur WHERE type_utilisateur = 'administrateur'")->fetchColumn();
    $suspendus = (int)$pdo->query('SELECT COUNT(*) FROM utilisateur WHERE suspendu = 1')->fetchColumn();
    eco_json_success([
        'total' => $total,
        'employes' => $employes,
        'admins' => $admins,
        'suspendus' => $suspendus
    ]);
} catch (Exception $e) {
    EcoLogger::log('stats', 'utilisateurs_error=' . $e->getMessage());
    eco_json_error('Erreur lors de la récupération des statistiques.', 500);
}
