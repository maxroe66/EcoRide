<?php
// Modèle pour la recherche de covoiturages (version traitement.php)
class RechercheCovoitTraitement {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function rechercher($lieu_depart, $lieu_arrivee, $date_depart, $prix_max, $ecologique) {
        $sql = "SELECT c.*, u.nom, u.prenom, v.modele, m.libelle AS marque, (c.nb_places - COALESCE(p.places_prises, 0)) as places_restantes
            FROM covoiturage c
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            LEFT JOIN (
                SELECT covoiturage_id, SUM(nb_places) as places_prises
                FROM participation
                WHERE statut = 'confirmee'
                GROUP BY covoiturage_id
            ) p ON c.covoiturage_id = p.covoiturage_id
            WHERE c.statut = 'planifie'
            AND c.date_depart >= CURDATE()
            AND c.prix_personne <= ?";
        $params = [$prix_max];
        if ($lieu_depart) {
            $sql .= " AND c.lieu_depart LIKE ?";
            $params[] = "%$lieu_depart%";
        }
        if ($lieu_arrivee) {
            $sql .= " AND c.lieu_arrivee LIKE ?";
            $params[] = "%$lieu_arrivee%";
        }
        if ($date_depart) {
            $sql .= " AND DATE(c.date_depart) = ?";
            $params[] = $date_depart;
        }
        if ($ecologique) {
            $sql .= " AND v.est_ecologique = 1";
        }
        $sql .= " ORDER BY c.date_depart ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
