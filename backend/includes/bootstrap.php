<?php
/**
 * Bootstrap central EcoRide
 * Objectifs :
 *  - Démarrage session sécurisé (idempotent)
 *  - Chargement config & autoload
 *  - Constantes de statuts
 *  - Helpers JSON (optionnels, n'affectent pas anciens contrôleurs)
 *  - Logger simple fichier
 *  - Fonctions utilitaires validation / sanitisation
 *  (Aucune modification automatique des en-têtes si on n'utilise pas les helpers)
 */

if (defined('ECO_BOOTSTRAP_LOADED')) {
	return; // Empêche double inclusion
}
define('ECO_BOOTSTRAP_LOADED', true);

// Chargement autoload & config base de données
// (config.php charge déjà vendor/autoload.php)
require_once __DIR__ . '/../config/config.php';

// --- Constantes métier globales (sécurise la duplication magique 2 / 20) ---
if (!defined('ECO_COMMISSION')) {
	define('ECO_COMMISSION', 2.0); // Commission fixe prélevée sur un trajet (MVP)
}
if (!defined('ECO_CREDIT_INITIAL')) {
	define('ECO_CREDIT_INITIAL', 20.0); // Crédit offert à l'inscription (US7)
}

// --- Session --------------------------------------------------------------
function eco_session_start(): void {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		@session_start();
	}
}
eco_session_start();

// --- CSRF (génération & vérification légère) ------------------------------
if (empty($_SESSION['csrf_token'])) {
	// Jeton aléatoire 32 bytes hex (sécurisé)
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function eco_csrf_token(): string {
	return $_SESSION['csrf_token'] ?? '';
}

function eco_verify_csrf(?string $token): void {
	if (!isset($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
		eco_json_error('CSRF token invalide', 403);
	}
}

// --- Constantes de statuts (utiliser partout au lieu de chaînes magiques) --
if (!defined('ECO_STATUT_PLANIFIE')) {
	define('ECO_STATUT_PLANIFIE', 'planifie');
	define('ECO_STATUT_EN_COURS', 'en_cours');
	define('ECO_STATUT_TERMINE', 'termine');
	define('ECO_STATUT_ANNULE', 'annule');
}

// --- Helpers JSON ---------------------------------------------------------
/**
 * Détecte si le client demande le format API v2 normalisé.
 * Activation par :
 *   - Paramètre de requête format=v2
 *   - En-tête HTTP Accept contenant "application/vnd.ecoride.v2+json"
 */
function eco_use_api_v2(): bool {
	$q = $_GET['format'] ?? $_POST['format'] ?? null;
	if ($q === 'v2') return true;
	$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
	if (stripos($accept, 'application/vnd.ecoride.v2+json') !== false) return true;
	return false;
}

/**
 * Construit la structure normalisée (v2):
 * {
 *   success: bool,
 *   data: {...},
 *   errors: [],
 *   meta: { format: 'v2', ts: ... }
 * }
 * Les contrôleurs legacy continuent d'appeler eco_json_success / error.
 * Si le client demande v2, on convertit automatiquement.
 */
function eco_build_v2_response(bool $success, array $payload = [], array $errors = [], array $meta = []): array {
	// Si le payload legacy contient déjà success, on le nettoie pour éviter duplication
	if (isset($payload['success'])) unset($payload['success']);
	return [
		'success' => $success,
		'data'    => $payload,
		'errors'  => $errors,
		'meta'    => array_merge(['format' => 'v2', 'ts' => date('c')], $meta)
	];
}

/**
 * Réponse succès JSON standard.
 * Usage (dans un contrôleur) :
 *   eco_json_success(['data' => $rows]);
 */
function eco_json_success(array $payload = [], int $code = 200, bool $exit = true): void {
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code($code);
	}
	if (eco_use_api_v2()) {
		$out = eco_build_v2_response(true, $payload);
		header('X-EcoRide-Api-Format: v2');
	} else {
		$out = array_merge(['success' => true], $payload); // legacy
	}
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($exit) exit;
}

/**
 * Réponse erreur JSON standard.
 * eco_json_error('Paramètre manquant', 400);
 */
function eco_json_error(string $message, int $code = 400, array $extra = [], bool $exit = true): void {
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=utf-8');
		http_response_code($code);
	}
	if (eco_use_api_v2()) {
		// On place message et extra dans data pour cohérence, et message principal dans errors[]
		$payload = array_merge(['message' => $message], $extra);
		$out = eco_build_v2_response(false, $payload, [$message]);
		header('X-EcoRide-Api-Format: v2');
	} else {
		$out = ['success' => false, 'message' => $message] + $extra; // legacy
	}
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($exit) exit;
}

// --- Logger simple --------------------------------------------------------
class EcoLogger {
	public static function log(string $channel, string $message): void {
		$dir = __DIR__ . '/../logs';
		if (!is_dir($dir)) {
			@mkdir($dir, 0775, true);
		}
		$file = $dir . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($channel)) . '.log';
		$line = date('c') . ' | ' . $message . PHP_EOL;
		@file_put_contents($file, $line, FILE_APPEND);
	}
}

// --- Sanitisation & validation -------------------------------------------
function eco_sanitize_string(?string $value): string {
	return trim((string)$value);
}

function eco_validate_date(string $date, string $format = 'Y-m-d'): bool {
	$dt = DateTime::createFromFormat($format, $date);
	return $dt !== false && $dt->format($format) === $date;
}

// --- (Optionnel) Vérification environnement -------------------------------
// Exemple : définir ECO_ENV=prod dans un fichier d'environnement plus tard.
if (!defined('ECO_ENV')) {
	define('ECO_ENV', 'dev');
}

// --- Auth helpers ---------------------------------------------------------
/**
 * Exige qu'un utilisateur soit connecté (présence user_id en session).
 * Retourne une réponse JSON 401 sinon.
 */
function eco_require_login(): void {
	if (!isset($_SESSION['user_id'])) {
		eco_json_error('Authentification requise', 401);
	}
}

/**
 * Exige qu'un rôle parmi la liste soit présent en session (type_utilisateur).
 * Exemple : eco_require_role(['administrateur']);
 */
function eco_require_role(array $roles): void {
	$role = $_SESSION['type_utilisateur'] ?? null;
	if (!$role || !in_array($role, $roles, true)) {
		eco_json_error('Accès refusé', 403);
	}
}

// Rien d'autre n'est exécuté ici pour éviter tout impact inattendu.
// Les anciens contrôleurs continuent donc de fonctionner tels quels.

// Chargement du helper de réponse unifié rétro-compatible (phase de transition)
// Ne remplace pas immédiatement les helpers existants mais fournit eco_json_response.
require_once __DIR__ . '/response_helper.php';

