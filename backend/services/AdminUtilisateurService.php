<?php
// Service d'administration pour gestion des utilisateurs (listing, suspension, création employé)
require_once __DIR__ . '/../models/Utilisateur.php';

class AdminUtilisateurService {
    private PDO $pdo;
    private Utilisateur $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->model = new Utilisateur($pdo);
    }

    /**
     * Liste tous les utilisateurs (potentiel: ajouter pagination plus tard)
     */
    public function listerTous(): array {
        try {
            $stmt = $this->pdo->query("SELECT utilisateur_id, nom, prenom, email, type_utilisateur, suspendu FROM utilisateur ORDER BY type_utilisateur, nom");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success'=>true,'utilisateurs'=>$rows];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_utilisateurs_error='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur lors de la récupération des utilisateurs.'];
        }
    }

    /**
     * Version paginée (OFFSET/LIMIT) 
     * @param int $page (>=1)
     * @param int $limit (1..100)
     */
    public function listerPagines(int $page = 1, int $limit = 20): array {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;
        try {
            $total = (int)$this->pdo->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
            $stmt = $this->pdo->prepare("SELECT utilisateur_id, nom, prenom, email, type_utilisateur, suspendu FROM utilisateur ORDER BY type_utilisateur, nom, utilisateur_id LIMIT :lim OFFSET :off");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalPages = (int)ceil(($total ?: 0)/$limit);
            return [
                'success'=>true,
                'utilisateurs'=>$rows,
                'meta'=>[
                    'page'=>$page,
                    'per_page'=>$limit,
                    'total'=>$total,
                    'total_pages'=>$totalPages,
                    'has_next'=>$page < $totalPages
                ]
            ];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_utilisateurs_page_error='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur pagination utilisateurs'];
        }
    }

    /**
     * Version paginée + filtres + tri.
     * Filtres acceptés:
     *  - type_utilisateur: standard|employe|administrateur
     *  - suspendu: 0|1
     *  - q: recherche plein texte (nom, prenom, email)
     * Tri:
     *  - sort_by dans whitelist (nom, date_creation, type_utilisateur, utilisateur_id, credit)
     *  - sort_dir asc|desc
     */
    public function listerPaginesFiltrees(int $page = 1, int $limit = 20, array $filters = [], array $sort = []): array {
        $page = max(1, $page); $limit = max(1, min(100, $limit)); $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];
        // type_utilisateur
        if (!empty($filters['type_utilisateur']) && in_array($filters['type_utilisateur'], ['standard','employe','administrateur'], true)) {
            $where[] = 'type_utilisateur = :type_utilisateur';
            $params[':type_utilisateur'] = $filters['type_utilisateur'];
        }
        // suspendu
        if (isset($filters['suspendu']) && ($filters['suspendu'] === '0' || $filters['suspendu'] === '1' || $filters['suspendu'] === 0 || $filters['suspendu'] === 1)) {
            $where[] = 'suspendu = :suspendu';
            $params[':suspendu'] = (int)$filters['suspendu'];
        }
        // recherche q
        if (!empty($filters['q'])) {
            $where[] = '(nom LIKE :q OR prenom LIKE :q OR email LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sortBy = $sort['sort_by'] ?? 'type_utilisateur';
        $dir = strtolower($sort['sort_dir'] ?? 'asc');
        $allowedSort = [
            'nom' => 'nom',
            'date_creation' => 'date_creation',
            'type_utilisateur' => 'type_utilisateur',
            'utilisateur_id' => 'utilisateur_id',
            'credit' => 'credit'
        ];
        if (!isset($allowedSort[$sortBy])) { $sortBy = 'type_utilisateur'; }
        $dir = $dir === 'desc' ? 'DESC' : 'ASC';
        try {
            // total
            $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM utilisateur $whereSql");
            foreach ($params as $k=>$v) { $stmtTotal->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmtTotal->execute();
            $total = (int)$stmtTotal->fetchColumn();
            $sql = "SELECT utilisateur_id, nom, prenom, email, type_utilisateur, suspendu, credit, date_creation FROM utilisateur $whereSql ORDER BY $sortBy $dir, utilisateur_id ASC LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) { $stmt->bindValue($k, $v, is_int($v)?PDO::PARAM_INT:PDO::PARAM_STR); }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $totalPages = (int)ceil(($total ?: 0)/$limit);
            return [
                'success'=>true,
                'utilisateurs'=>$rows,
                'meta'=>[
                    'page'=>$page,
                    'per_page'=>$limit,
                    'total'=>$total,
                    'total_pages'=>$totalPages,
                    'has_next'=>$page < $totalPages,
                    'filters'=>[ 'type_utilisateur'=>$filters['type_utilisateur'] ?? null, 'suspendu'=>$filters['suspendu'] ?? null, 'q'=>$filters['q'] ?? null ],
                    'sort'=>['by'=>$sortBy,'dir'=>$dir]
                ]
            ];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'lister_utilisateurs_filtres_error='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur filtres/tri utilisateurs'];
        }
    }

    /**
     * Change suspension d'un utilisateur
     */
    public function changerSuspension(int $utilisateurId, bool $suspendre): array {
        if ($utilisateurId <= 0) { return ['success'=>false,'message'=>'ID utilisateur invalide']; }
        try {
            $stmt = $this->pdo->prepare('UPDATE utilisateur SET suspendu = ? WHERE utilisateur_id = ?');
            $ok = $stmt->execute([$suspendre ? 1 : 0, $utilisateurId]);
            if (!$ok) { return ['success'=>false,'message'=>'Échec mise à jour suspension']; }
            return ['success'=>true,'message'=>$suspendre ? 'Utilisateur suspendu' : 'Utilisateur réactivé'];
        } catch (Exception $e) {
            EcoLogger::log('admin', 'suspend_error user_id='.$utilisateurId.' msg='.$e->getMessage());
            return ['success'=>false,'message'=>'Erreur lors de la suspension/réactivation.'];
        }
    }

    /**
     * Crée un employé (type_utilisateur=employe)
     */
    public function creerEmploye(array $data): array {
        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $motDePasse = $data['mot_de_passe'] ?? '';
        $telephone = trim($data['telephone'] ?? '');
        if (!$nom || !$prenom || !$email || !$motDePasse) {
            return ['success'=>false,'message'=>'Tous les champs sont obligatoires.'];
        }
        try {
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, password, telephone, type_utilisateur, credit) VALUES (?, ?, ?, ?, ?, 'employe', 0.00)");
            $stmt->execute([$nom, $prenom, $email, $hash, $telephone]);
            return ['success'=>true,'message'=>'Employé créé'];
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') { return ['success'=>false,'message'=>'Cet email est déjà utilisé.','code'=>409]; }
            return ['success'=>false,'message'=>'Échec création employé','code'=>500];
        } catch (Exception $e) {
            return ['success'=>false,'message'=>'Erreur lors de la création de l\'employé.','code'=>500];
        }
    }
}
