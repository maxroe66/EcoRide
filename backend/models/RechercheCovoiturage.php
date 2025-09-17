<?php
// Modèle pour l'accès aux données de recherche de covoiturages
// Toutes les requêtes SQL liées à la recherche sont centralisées ici

class RechercheCovoiturage {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Recherche les covoiturages selon les critères fournis
     */
    public function rechercher($criteres) {
        // NOTE: Remplacement de l'ancienne note aléatoire par une moyenne réelle basée sur avis_fallback.
        // Pour rester débutant friendly, on utilise une sous-requête simple par conducteur.
        // Si aucun avis validé (répliqué), la moyenne vaut 0.
        $base = "
            SELECT
                c.covoiturage_id,
                c.date_depart,
                c.heure_depart,
                c.heure_arrivee,
                c.lieu_depart,
                c.lieu_arrivee,
                c.nb_places,
                c.prix_personne,
                c.statut,
                c.est_ecologique,
                u.prenom,
                u.nom,
                u.pseudo,
                u.email,
                m.libelle AS marque,
                v.modele,
                v.couleur,
                v.est_ecologique as voiture_ecologique,
                COALESCE((
                    SELECT ROUND(AVG(a.note), 1)
                    FROM avis_fallback a
                    JOIN covoiturage c2 ON a.covoiturage_id = c2.covoiturage_id
                    WHERE c2.conducteur_id = c.conducteur_id
                    AND a.note BETWEEN 1 AND 5
                ), 0) AS note_moyenne,
                CASE 
                    WHEN c.heure_arrivee IS NULL OR c.heure_arrivee = '00:00:00' THEN NULL
                    ELSE TIMESTAMPDIFF(MINUTE, CONCAT(c.date_depart,' ', c.heure_depart), CONCAT(c.date_depart,' ', c.heure_arrivee))
                END AS duree_minutes
            FROM covoiturage c
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            JOIN voiture v ON c.voiture_id = v.voiture_id
            JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.statut = 'planifie'
            AND c.nb_places >= ?
        ";
        $sql = $base; // sera éventuellement encapsulé
        $params = [$criteres['nb_places_min']];
        if (!empty($criteres['lieu_depart'])) {
            if (!empty($criteres['partial_match'])) {
                $sql .= " AND LOWER(c.lieu_depart) LIKE LOWER(?)";
                $params[] = '%' . $criteres['lieu_depart'] . '%';
            } else {
                $sql .= " AND LOWER(TRIM(c.lieu_depart)) = LOWER(TRIM(?))";
                $params[] = $criteres['lieu_depart'];
            }
        }
        if (!empty($criteres['lieu_arrivee'])) {
            if (!empty($criteres['partial_match'])) {
                $sql .= " AND LOWER(c.lieu_arrivee) LIKE LOWER(?)";
                $params[] = '%' . $criteres['lieu_arrivee'] . '%';
            } else {
                $sql .= " AND LOWER(TRIM(c.lieu_arrivee)) = LOWER(TRIM(?))";
                $params[] = $criteres['lieu_arrivee'];
            }
        }
        // Date exacte OU plage
        if (!empty($criteres['date_depart'])) {
            $sql .= " AND c.date_depart = ?";
            $params[] = $criteres['date_depart'];
        } else {
            if (!empty($criteres['date_from'])) { $sql .= " AND c.date_depart >= ?"; $params[] = $criteres['date_from']; }
            if (!empty($criteres['date_to'])) { $sql .= " AND c.date_depart <= ?"; $params[] = $criteres['date_to']; }
        }
        if ($criteres['prix_max']) {
            $sql .= " AND c.prix_personne <= ?";
            $params[] = $criteres['prix_max'];
        }
        if ($criteres['ecologique_only']) {
            $sql .= " AND (c.est_ecologique = 1 OR v.est_ecologique = 1)";
        }
        // Filtres post-calcul sur agrégats individuels (note/durée) via HAVING simulé en WHERE sur alias calculés ne marche pas en MySQL avant SELECT fini, on encapsule donc si besoin.
        $needsFilter = $criteres['min_rating'] || $criteres['max_duration'];
        $filterParams = [];
        if ($needsFilter) {
            $conditions = [];
            if ($criteres['min_rating']) { $conditions[] = 't.note_moyenne >= ?'; $filterParams[] = $criteres['min_rating']; }
            if ($criteres['max_duration']) { $conditions[] = '(t.duree_minutes IS NOT NULL AND t.duree_minutes <= ?)'; $filterParams[] = $criteres['max_duration']; }
            $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM (' . $sql . ') t WHERE 1=1';
            if ($conditions) { $sql .= ' AND ' . implode(' AND ', $conditions); }
        } else {
            // Ajouter SQL_CALC_FOUND_ROWS uniquement ici si pas d'encapsulation
            $sql = preg_replace('/^\s*SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS', $sql, 1);
        }
        $sql .= " ORDER BY date_depart ASC, heure_depart ASC";
        $page = max(1, (int)($criteres['page'] ?? 1));
        $perPage = min(100, max(1, (int)($criteres['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";

        // Préparer & exécuter
        $stmt = $this->pdo->prepare($sql);
        $execParams = array_merge($params, $filterParams);
        $stmt->execute($execParams);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$this->pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Cherche la prochaine date disponible correspondant (partiellement) aux critères
     * lorsque la date demandée n'a donné aucun résultat.
     * Retourne [ 'date' => 'YYYY-MM-DD', 'count' => int ] ou null.
     */
    public function suggererProchaineDate(array $criteres): ?array {
        if (empty($criteres['date_depart'])) return null; // Sans date de départ on ne suggère rien

        $where   = ["c.statut = 'planifie'", 'c.nb_places >= :nb_places', 'c.date_depart > :current_date'];
        $params  = [
            ':nb_places'    => $criteres['nb_places_min'],
            ':current_date' => $criteres['date_depart']
        ];
        if ($criteres['lieu_depart'])  { $where[] = 'LOWER(TRIM(c.lieu_depart)) = LOWER(TRIM(:lieu_depart))';   $params[':lieu_depart']  = $criteres['lieu_depart']; }
        if ($criteres['lieu_arrivee']) { $where[] = 'LOWER(TRIM(c.lieu_arrivee)) = LOWER(TRIM(:lieu_arrivee))'; $params[':lieu_arrivee'] = $criteres['lieu_arrivee']; }
        if ($criteres['prix_max'])     { $where[] = 'c.prix_personne <= :prix_max';                             $params[':prix_max']     = $criteres['prix_max']; }
        if ($criteres['ecologique_only']) { $where[] = '(c.est_ecologique = 1 OR v.est_ecologique = 1)'; }

        $sqlMin = "SELECT MIN(c.date_depart) AS next_date
                    FROM covoiturage c
                    JOIN voiture v ON c.voiture_id = v.voiture_id
                    WHERE " . implode(' AND ', $where);
        $stmtMin = $this->pdo->prepare($sqlMin);
        $stmtMin->execute($params);
        $nextDate = $stmtMin->fetchColumn();
        if (!$nextDate) return null;

        // Compter le nombre de trajets à cette date
        $whereCount = $where;
        foreach ($whereCount as $k => $cond) {
            if (strpos($cond, 'c.date_depart >') !== false) { $whereCount[$k] = 'c.date_depart = :target_date'; }
        }
        $sqlCount = "SELECT COUNT(*) FROM covoiturage c JOIN voiture v ON c.voiture_id = v.voiture_id WHERE " . implode(' AND ', $whereCount);
        $paramsCount = $params;
        unset($paramsCount[':current_date']);
        $paramsCount[':target_date'] = $nextDate;
        $stmtCount = $this->pdo->prepare($sqlCount);
        $stmtCount->execute($paramsCount);
        $countNext = (int)$stmtCount->fetchColumn();
        return [ 'date' => $nextDate, 'count' => $countNext ];
    }
}
