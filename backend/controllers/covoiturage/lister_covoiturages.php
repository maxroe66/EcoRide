<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/CovoiturageAdminService.php';
eco_require_role(['administrateur']);

$pdo = Database::getMySQL();
$service = new CovoiturageAdminService($pdo);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$useAdvanced = ($page || $limit) || isset($_GET['statut']) || isset($_GET['conducteur_id']) || isset($_GET['date_min']) || isset($_GET['date_max']) || isset($_GET['q']) || isset($_GET['sort_by']) || isset($_GET['sort_dir']);
if ($useAdvanced) {
	$filters = [
		'statut' => $_GET['statut'] ?? null,
		'conducteur_id' => $_GET['conducteur_id'] ?? null,
		'date_min' => $_GET['date_min'] ?? null,
		'date_max' => $_GET['date_max'] ?? null,
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
			'per_page'=>count($result['covoiturages'] ?? []),
			'total'=>count($result['covoiturages'] ?? []),
			'total_pages'=>1,
			'has_next'=>false
		];
	}
}
if (!isset($result['success'])) { eco_json_error('Réponse service invalide'); }
if ($result['success'] === false) { eco_json_error($result['message'] ?? 'Erreur listing', 500); }
eco_json_response([
	'covoiturages' => $result['covoiturages'] ?? [],
	'meta' => $result['meta'] ?? null,
	'message' => $result['message'] ?? null
]);
