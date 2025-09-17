<?php
// Routeur :
// - Charge la liste des routes
// - Compare méthode + chemin exact
// - Applique règles basiques (auth / rôle)
// - Inclut le fichier contrôleur correspondant
// Si aucune route ne correspond : renvoie 404 JSON

class Router {
    private array $routes = [];
    private string $controllersDir;

    public function __construct(string $controllersDir, array $routes) {
        $this->controllersDir = rtrim($controllersDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->routes = $routes;
    }

    public function handle(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // On récupère uniquement le chemin (sans la query string)
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // Exemple: /EcoRide/api/ping alors que les routes sont définies en /api/ping
        // On calcule aussi une version alternative sans le premier segment (dossier projet)
        $altUri = $requestUri;
        if (substr_count($requestUri, '/') >= 2) { // au moins /segment/suite
            $altUri = preg_replace('#^/[^/]+#', '', $requestUri); // retire /PremierSegment
            if ($altUri === '') { $altUri = '/'; }
        }

        foreach ($this->routes as $route) {
            // Chaque route : [METHOD, PATH, CONTROLLER, RULE?]
            $rMethod = $route[0];
            $rPath   = $route[1];
            $rFile   = $route[2];
            $rRule   = $route[3] ?? null;

            if ($method === $rMethod && ($requestUri === $rPath || $altUri === $rPath)) {
                // Avant d'inclure le contrôleur, on applique la règle éventuelle
                if ($rRule) {
                    $this->applyRule($rRule);
                }
                // On définit une constante pour indiquer que l'accès passe par le routeur
                if (!defined('ECO_VIA_ROUTER')) {
                    define('ECO_VIA_ROUTER', true);
                }
                $fullPath = $this->controllersDir . $rFile;
                if (!is_file($fullPath)) {
                    $this->jsonError('Contrôleur introuvable', 500);
                }
                require $fullPath;
                return; // Contrôleur a terminé (souvent exit après eco_json_*)
            }
        }

        // Aucune route trouvée
        $this->jsonError('Route non trouvée', 404);
    }

    private function applyRule(string $rule): void {
        // 'auth' => exige présence user_id en session
        // 'role:administrateur' => exige rôle précis
        if ($rule === 'auth') {
            if (empty($_SESSION['user_id'])) {
                $this->jsonError('Authentification requise', 401);
            }
            return;
        }
        if (str_starts_with($rule, 'role:')) {
            $needed = substr($rule, 5);
            $role = $_SESSION['type_utilisateur'] ?? null;
            if ($role !== $needed) { $this->jsonError('Accès refusé', 403); }
            return;
        }
        if (str_starts_with($rule, 'roles:')) {
            $list = trim(substr($rule, 6));
            $allowed = array_filter(array_map('trim', explode(',', $list)));
            $role = $_SESSION['type_utilisateur'] ?? null;
            if (!$role || !in_array($role, $allowed, true)) { $this->jsonError('Accès refusé', 403); }
            return;
        }
    }

    private function jsonError(string $message, int $code): void {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => $message]);
    }
}
