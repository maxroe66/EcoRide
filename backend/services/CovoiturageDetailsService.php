<?php
// services/CovoiturageDetailsService.php
// Service pour la logique métier liée à la récupération des détails d'un covoiturage
// Ce fichier centralise la validation, l'appel au modèle et le formatage des données

require_once __DIR__ . '/../models/CovoiturageDetails.php';
require_once __DIR__ . '/AvisFacadeService.php';

class CovoiturageDetailsService
{
    private $pdo;
    private $detailsModel;

    // Constructeur : on passe la connexion PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->detailsModel = new CovoiturageDetails($pdo);
    }

    // Fonction principale pour récupérer les détails d'un covoiturage
    public function getDetails($id)
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Paramètre id manquant ou invalide'];
        }

        $row = $this->detailsModel->getDetails($id);
        if (!$row) {
            return ['success' => false, 'error' => 'Covoiturage introuvable'];
        }

        // Calculer le nombre de places disponibles
        $places_disponibles = $this->detailsModel->getPlacesDisponibles($id, $row['nb_places']);

        // ==== Avis & notes via façade unifiée ====
        $avisFacade = new AvisFacadeService($this->pdo);
        $avisTrajet = $avisFacade->listerAvisTrajet((int)$id, 5); // avis + moyenne trajet
        $statsCond = $avisFacade->statsConducteur((int)$row['utilisateur_id']);
        $tousAvis = $avisTrajet['avis'] ?? [];
        $ratingTrajet = $avisTrajet['moyenne_trajet'] ?? 0.0;
        $ratingGlobal = $statsCond['moyenne_globale'] ?? 0.0;

        // Initiales du conducteur
        $initiales = strtoupper(substr($row['prenom'], 0, 1) . substr($row['nom'], 0, 1));

        // Déterminer si le trajet ou la voiture est écologique
        $isEco = ((int)$row['cov_ecologique'] === 1) || ((int)$row['voiture_ecologique'] === 1);

        // Préférences du conducteur
        $prefs = [];
        if ($row['preference_fumeur'] === 'accepte') {
            $prefs[] = '🚬 Fumeur accepté';
        } else {
            $prefs[] = '💭 Non-fumeur';
        }
        if ($row['preference_animaux'] === 'accepte') {
            $prefs[] = '🐶 Animaux autorisés';
        } else {
            $prefs[] = '🚫 Animaux non autorisés';
        }
        if (!empty($row['autres_preferences'])) {
            $prefs[] = 'ℹ️ ' . $row['autres_preferences'];
        }

        // Energie du véhicule (première lettre en majuscule)
        $energie = $row['energie'] ? ucfirst($row['energie']) : '';

        // Année et date ISO de première immatriculation
        $annee = null;
        $datePremiereISO = null;
        if (!empty($row['date_premiere_immatriculation'])) {
            $dtPremiere = new DateTime($row['date_premiere_immatriculation']);
            $annee = $dtPremiere->format('Y');
            $datePremiereISO = $dtPremiere->format('Y-m-d');
        }

        // Calcul de la durée estimée du trajet
        $duration = 'À définir';
        if (!empty($row['heure_depart']) && !empty($row['heure_arrivee']) && $row['heure_arrivee'] !== '00:00:00') {
            $d1 = new DateTime($row['heure_depart']);
            $d2 = new DateTime($row['heure_arrivee']);
            if ($d2 < $d1) { // cas minuit +
                $d2->modify('+1 day');
            }
            $interval = $d1->diff($d2);
            $h = (int)$interval->format('%h');
            $m = (int)$interval->format('%i');
            $duration = $h . 'h' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
        }

        // Préparer la réponse pour le front-end
    $pseudo = $row['pseudo'] ?: ($row['prenom'] . '_' . substr($row['nom'], 0, 1));
    $data = [
            'id' => (int)$row['covoiturage_id'],
            'driver' => [
        'pseudo' => $pseudo,
                // rating: moyenne globale chauffeur (cohérence avec liste)
                'rating' => (float)$ratingGlobal,
                'ratingTrip' => (float)$ratingTrajet,
                'initials' => $initiales,
                'isEcological' => $isEco
            ],
            'departure' => $row['lieu_depart'],
            'arrival' => $row['lieu_arrivee'],
            'date' => (new DateTime($row['date_depart']))->format('Y-m-d'),
            'departureTime' => (new DateTime($row['heure_depart']))->format('H\hi'),
            'arrivalTime' => (!empty($row['heure_arrivee']) && $row['heure_arrivee'] !== '00:00:00') ? (new DateTime($row['heure_arrivee']))->format('H\hi') : 'À définir',
            'duration' => $duration,
            'price' => (float)$row['prix_personne'],
            'availableSeats' => (int)$places_disponibles,
            'vehicle' => [
                'brand' => $row['marque'],
                'model' => $row['modele'],
                'color' => $row['couleur'],
                'energy' => $energie ?: '—',
                'year' => $annee ?: '—',
                'firstRegistrationDate' => $datePremiereISO
            ],
            'preferences' => $prefs,
            'reviews' => $tousAvis
        ];

        return ['success' => true, 'data' => $data];
    }
}
