<?php
// models/CovoiturageDetails.php
// Modèle pour la récupération des détails d'un covoiturage
// Ce fichier contient les fonctions d'accès à la base de données pour les infos détaillées

class CovoiturageDetails
{
    private $pdo;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Récupérer les infos principales du covoiturage, du conducteur, du véhicule et de la marque
    public function getDetails($id)
    {
        $sql = "
            SELECT 
                c.covoiturage_id,
                c.date_depart,
                c.heure_depart,
                c.heure_arrivee,
                c.lieu_depart,
                c.lieu_arrivee,
                c.nb_places,
                c.prix_personne,
                c.est_ecologique AS cov_ecologique,
                u.utilisateur_id,
                u.prenom,
                u.nom,
                u.pseudo,
                u.preference_fumeur,
                u.preference_animaux,
                u.autres_preferences,
                v.modele,
                v.couleur,
                v.energie,
                v.est_ecologique AS voiture_ecologique,
                v.date_premiere_immatriculation,
                m.libelle AS marque
            FROM covoiturage c
            JOIN utilisateur u ON c.conducteur_id = u.utilisateur_id
            JOIN voiture v ON c.voiture_id = v.voiture_id
            JOIN marque m ON v.marque_id = m.marque_id
            WHERE c.covoiturage_id = ?
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Calculer le nombre de places disponibles
    public function getPlacesDisponibles($id, $nb_places_total)
    {
        $sql = "SELECT COALESCE(SUM(nb_places), 0) AS prises FROM participation WHERE covoiturage_id = ? AND statut = 'confirmee'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $prises = (int)($stmt->fetchColumn() ?: 0);
        return max(0, (int)$nb_places_total - $prises);
    }

    // Récupérer les avis (table 'avis_fallback' désormais garantie par database.sql)
    public function getAvis($id)
    {
        $avis = [];
        $sql = "SELECT a.note, a.commentaire, u.prenom, u.nom FROM avis_fallback a JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id WHERE a.covoiturage_id = ? ORDER BY a.date_creation DESC LIMIT 5";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $avis[] = [
                    'author' => $r['prenom'] . ' ' . strtoupper(substr($r['nom'], 0, 1)) . '.',
                    'rating' => (int)$r['note'],
                    'text'   => $r['commentaire'] ?: ''
                ];
            }
        } catch (Exception $e) {
            // En cas d'anomalie (ex: table manquante en environnement non migré), on renvoie simplement une liste vide.
        }
        return $avis;
    }
}
