<?php
// Service d'administration pour opérations de listing/gestion globales des covoiturages
// Extrait depuis le contrôleur lister_covoiturages.php pour garder les contrôleurs minces.

class CovoiturageAdminService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retourne la liste complète des covoiturages avec conducteur (usage admin)
     */
    public function listerTous(): array {
        try {
            $sql = "SELECT c.covoiturage_id, c.date_depart, c.heure_depart, c.lieu_depart, c.lieu_arrivee, c.nb_places, c.prix_personne, c.statut, u.nom AS conducteur_nom, u.prenom AS conducteur_prenom FROM covoiturage c JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id ORDER BY c.date_depart DESC, c.heure_depart DESC";
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success'=>true,'covoiturages'=>$rows];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_covoiturages_error=' . $e->getMessage());
            return ['success'=>false,'message'=>'Erreur lors de la récupération des covoiturages.'];
        }
    }

    /**
     * Pagination simple OFFSET/LIMIT pour covoiturages.
     */
    public function listerPagines(int $page = 1, int $limit = 20): array {
        $page = max(1, $page); $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;
        try {
            $total = (int)$this->pdo->query('SELECT COUNT(*) FROM covoiturage')->fetchColumn();
            $sql = "SELECT c.covoiturage_id, c.date_depart, c.heure_depart, c.lieu_depart, c.lieu_arrivee, c.nb_places, c.prix_personne, c.statut, u.nom AS conducteur_nom, u.prenom AS conducteur_prenom FROM covoiturage c JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id ORDER BY c.date_depart DESC, c.heure_depart DESC, c.covoiturage_id DESC LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalPages = (int)ceil(($total ?: 0)/$limit);
            return [
                'success'=>true,
                'covoiturages'=>$rows,
                'meta'=>[
                    'page'=>$page,
                    'per_page'=>$limit,
                    'total'=>$total,
                    'total_pages'=>$totalPages,
                    'has_next'=>$page < $totalPages
                ]
            ];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_covoiturages_page_error=' . $e->getMessage());
            return ['success'=>false,'message'=>'Erreur pagination covoiturages'];
        }
    }

    /**
     * Pagination + filtres + tri pour covoiturages.
     * Filtres:
     *  - statut: planifie|en_cours|termine|annule
     *  - conducteur_id
     *  - date_min (YYYY-MM-DD)
     *  - date_max (YYYY-MM-DD)
     *  - q (lieu_depart/lieu_arrivee LIKE)
     * Tri: sort_by dans (date_depart, prix_personne, nb_places, statut, covoiturage_id) + sort_dir asc|desc
     */
    public function listerPaginesFiltrees(int $page = 1, int $limit = 20, array $filters = [], array $sort = []): array {
        $page = max(1, $page); $limit = max(1, min(100, $limit)); $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        if (!empty($filters['statut']) && in_array($filters['statut'], ['planifie','en_cours','termine','annule'], true)) {
            $where[] = 'c.statut = :statut';
            $params[':statut'] = $filters['statut'];
        }
        if (!empty($filters['conducteur_id']) && ctype_digit((string)$filters['conducteur_id'])) {
            $where[] = 'c.conducteur_id = :conducteur_id';
            $params[':conducteur_id'] = (int)$filters['conducteur_id'];
        }
        if (!empty($filters['date_min'])) {
            $where[] = 'c.date_depart >= :date_min';
            $params[':date_min'] = $filters['date_min'];
        }
        if (!empty($filters['date_max'])) {
            $where[] = 'c.date_depart <= :date_max';
            $params[':date_max'] = $filters['date_max'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.lieu_depart LIKE :q OR c.lieu_arrivee LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sortBy = $sort['sort_by'] ?? 'date_depart';
        $dir = strtolower($sort['sort_dir'] ?? 'desc');
        $allowedSort = [
            'date_depart' => 'c.date_depart',
            'prix_personne' => 'c.prix_personne',
            'nb_places' => 'c.nb_places',
            'statut' => 'c.statut',
            'covoiturage_id' => 'c.covoiturage_id'
        ];
        if (!isset($allowedSort[$sortBy])) { $sortBy = 'date_depart'; }
        $orderExpr = $allowedSort[$sortBy];
        $dir = $dir === 'asc' ? 'ASC' : 'DESC';
        try {
            $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM covoiturage c $whereSql");
            foreach ($params as $k=>$v) { $stmtTotal->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmtTotal->execute();
            $total = (int)$stmtTotal->fetchColumn();
            $sql = "SELECT c.covoiturage_id, c.date_depart, c.heure_depart, c.lieu_depart, c.lieu_arrivee, c.nb_places, c.prix_personne, c.statut, u.nom AS conducteur_nom, u.prenom AS conducteur_prenom FROM covoiturage c JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id $whereSql ORDER BY $orderExpr $dir, c.covoiturage_id DESC LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalPages = (int)ceil(($total ?: 0)/$limit);
            return [
                'success'=>true,
                'covoiturages'=>$rows,
                'meta'=>[
                    'page'=>$page,
                    'per_page'=>$limit,
                    'total'=>$total,
                    'total_pages'=>$totalPages,
                    'has_next'=>$page < $totalPages,
                    'filters'=>[
                        'statut'=>$filters['statut'] ?? null,
                        'conducteur_id'=>$filters['conducteur_id'] ?? null,
                        'date_min'=>$filters['date_min'] ?? null,
                        'date_max'=>$filters['date_max'] ?? null,
                        'q'=>$filters['q'] ?? null
                    ],
                    'sort'=>['by'=>$sortBy,'dir'=>$dir]
                ]
            ];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_covoiturages_filtres_error=' . $e->getMessage());
            return ['success'=>false,'message'=>'Erreur filtres/tri covoiturages'];
        }
    }
}
