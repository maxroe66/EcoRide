<?php
// Modèle pour l'accès aux données de l'historique des covoiturages
// Toutes les requêtes SQL liées à l'historique sont centralisées ici

class HistoriqueCovoiturage {
    private $pdo;

    /**
     * Constructeur : injection de la connexion PDO
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les covoiturages proposés par l'utilisateur (en tant que chauffeur)
     */
    public function getCovoituragesProposes($userId) {
    // Création dynamique de la table incident supprimée (maintenant dans database.sql)

        // Ajout d'un LEFT JOIN agrégé pour compter le nombre de places déjà réservées (participants actifs)
        // + un LEFT JOIN incidents en cours (statut='en_cours') pour indiquer au chauffeur les litiges ouverts
    $sql = "
            SELECT 
                c.covoiturage_id,
                c.lieu_depart,
                c.lieu_arrivee,
                c.date_depart,
                c.heure_depart,
                c.heure_arrivee,
                c.nb_places,
                c.prix_personne,
                c.statut,
                m.libelle AS marque,
                v.modele,
                v.est_ecologique AS voiture_ecologique,
                COALESCE(pr.participants_reserves, 0) AS participants_reserves,
                COALESCE(inc.incidents_ouverts, 0) AS incidents_ouverts,
                'chauffeur' as role
            FROM covoiturage c
            JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN marque m ON v.marque_id = m.marque_id
            LEFT JOIN (
                SELECT covoiturage_id, SUM(nb_places) AS participants_reserves
                FROM participation
                WHERE statut NOT IN ('annulee')
                GROUP BY covoiturage_id
            ) pr ON pr.covoiturage_id = c.covoiturage_id
            LEFT JOIN (
                SELECT i.covoiturage_id, COUNT(DISTINCT i.incident_id) AS incidents_ouverts
                FROM incident i
                JOIN participation p2 ON p2.participation_id = i.participation_id
                WHERE i.statut = 'en_cours' AND p2.statut = 'probleme'
                GROUP BY i.covoiturage_id
            ) inc ON inc.covoiturage_id = c.covoiturage_id
            WHERE c.conducteur_id = ?
            ORDER BY c.date_depart DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les participations de l'utilisateur (en tant que passager)
     */
    public function getParticipations($userId) {
        $sql = "
            SELECT 
                c.covoiturage_id,
                c.lieu_depart,
                c.lieu_arrivee,
                c.date_depart,
                c.heure_depart,
                c.heure_arrivee,
                c.prix_personne,
                c.est_ecologique AS covoiturage_ecologique,
                v.est_ecologique AS voiture_ecologique,
                p.nb_places as places_reservees,
                p.statut as statut_participation,
                p.participation_id,
                e.statut as escrow_statut,
                'passager' as role
            FROM participation p
            JOIN covoiturage c ON p.covoiturage_id = c.covoiturage_id
            JOIN voiture v ON c.voiture_id = v.voiture_id
            LEFT JOIN escrow_transaction e ON e.participation_id = p.participation_id
            WHERE p.utilisateur_id = ?
            ORDER BY c.date_depart DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
