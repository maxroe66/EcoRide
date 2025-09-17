<?php
// Service pour la logique métier liée à la recherche de covoiturages
// Ce service utilise le modèle RechercheCovoiturage

require_once __DIR__ . '/../models/RechercheCovoiturage.php';

class RechercheCovoiturageService {
    private $model;

    /**
     * Constructeur : injection du modèle
     */
    public function __construct($pdo) {
        $this->model = new RechercheCovoiturage($pdo);
    }

    /**
     * Recherche et formate les covoiturages selon les critères
     */
    public function rechercher($criteres) {
        $res = $this->model->rechercher($criteres); // ['rows'=>[], 'total'=>, 'page'=>, 'per_page'=>]
        $covoiturages = $res['rows'];
        $results = [];
        foreach ($covoiturages as $row) {
            $initiales = strtoupper(substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1));
            $est_ecologique = $row['est_ecologique'] || $row['voiture_ecologique'];
            $heure_depart = $row['heure_depart'] ? date('H\hi', strtotime($row['heure_depart'])) : '00h00';
            $heure_arrivee = ($row['heure_arrivee'] && $row['heure_arrivee'] !== '00:00:00') ? date('H\hi', strtotime($row['heure_arrivee'])) : 'À définir';
            $duree_formatee = 'À définir';
            if (isset($row['duree_minutes']) && $row['duree_minutes'] !== null && $row['duree_minutes'] >= 0) {
                $h = intdiv($row['duree_minutes'], 60);
                $m = $row['duree_minutes'] % 60;
                $duree_formatee = sprintf('%dh%02d', $h, $m);
            }
        $pseudo = $row['pseudo'] ?: ($row['prenom'] . '_' . substr($row['nom'], 0, 1));
        $results[] = [
                'id' => $row['covoiturage_id'],
                'driver' => [
            'pseudo' => $pseudo,
                    'rating' => (float)$row['note_moyenne'],
                    'initials' => $initiales,
                    'isEcological' => $est_ecologique
                ],
                'departureTime' => $heure_depart,
                'arrivalTime' => $heure_arrivee,
                'duration' => $duree_formatee,
                'price' => (float)$row['prix_personne'],
                'availableSeats' => (int)$row['nb_places'],
                'car' => $row['marque'] . ' ' . $row['modele'],
                'departure' => $row['lieu_depart'],
                'arrival' => $row['lieu_arrivee'],
                'date' => date('Y-m-d', strtotime($row['date_depart'])),
                'status' => $row['statut']
            ];
        }
        $count = count($results);
        $total = (int)$res['total'];
        $page = (int)$res['page'];
        $perPage = (int)$res['per_page'];
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
        return [
            'success' => true,
            'data' => $results,
            'count' => $count,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Variante enrichie : inclut éventuellement une suggestion si aucun résultat trouvé
     * et qu'une date précise a été fournie.
     */
    public function rechercherAvecSuggestion(array $criteres): array {
        $base = $this->rechercher($criteres); // success/data/count/pagination
        if ($base['count'] === 0 && !empty($criteres['date_depart'])) {
            $suggestion = $this->model->suggererProchaineDate($criteres);
            if ($suggestion) {
                $base['suggestion'] = $suggestion;
            }
        }
        // Pour compat rétro (contrôleur utilisait results + data) on ajoute alias
        $base['results'] = $base['data'];
        $base['schema_version'] = 1;
        return $base;
    }
}
