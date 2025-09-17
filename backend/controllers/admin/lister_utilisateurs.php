<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AdminUtilisateurService.php';
eco_require_role(['administrateur']);
$pdo = Database::getMySQL();
$service = new AdminUtilisateurService($pdo);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$useAdvanced = ($page || $limit) || isset($_GET['type_utilisateur']) || isset($_GET['suspendu']) || isset($_GET['q']) || isset($_GET['sort_by']) || isset($_GET['sort_dir']);
if ($useAdvanced) {
	$filters = [
		'type_utilisateur' => $_GET['type_utilisateur'] ?? null,
		'suspendu' => $_GET['suspendu'] ?? null,
		'q' => $_GET['q'] ?? null
	];
	$sort = [
		'sort_by' => $_GET['sort_by'] ?? null,
		'sort_dir' => $_GET['sort_dir'] ?? null
	];
	$result = $service->listerPaginesFiltrees($page, $limit, $filters, $sort);
} else {
	$result = $service->listerTous();
	if (($result['success'] ?? false) && !isset($result['meta'])) {
		$result['meta'] = [
			'page'=>1,
			'per_page'=>count($result['utilisateurs'] ?? []),
			'total'=>count($result['utilisateurs'] ?? []),
			'total_pages'=>1,
			'has_next'=>false
		];
	}
}
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur utilisateurs', 500); }
// Contrat stabilisé: toujours un tableau 'utilisateurs' (même vide) + meta + agrégats de rôles
$users = $result['utilisateurs'] ?? [];
$meta = $result['meta'] ?? null;
if (!$meta) {
	$meta = [
		'page' => 1,
		'per_page' => count($users),
		'total' => count($users),
		'total_pages' => 1,
		'has_next' => false
	];
}
// Calcul agrégats si non fournis (total, employes, admins, suspendus)
$total = $result['total'] ?? count($users);
$employes = $result['employes'] ?? count(array_filter($users, fn($u) => ($u['type_utilisateur'] ?? null) === 'employe'));
$admins = $result['admins'] ?? count(array_filter($users, fn($u) => ($u['type_utilisateur'] ?? null) === 'administrateur'));
$suspendus = $result['suspendus'] ?? count(array_filter($users, fn($u) => ($u['suspendu'] ?? 0) == 1));
eco_json_response([
	'schema_version' => 1,
	'utilisateurs' => $users,
	'meta' => $meta,
	'total' => $total,
	'employes' => $employes,
	'admins' => $admins,
	'suspendus' => $suspendus,
	'message' => $result['message'] ?? null
]);
