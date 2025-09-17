<?php
// Service pour la logique métier liée à la participation à un covoiturage
require_once __DIR__ . '/../models/ParticipationCovoiturage.php';
require_once __DIR__ . '/EscrowService.php';

class ParticipationCovoiturageService {
    private $model;
    public function __construct($pdo) { $this->model = new ParticipationCovoiturage($pdo); }

    public function participer($user_id, $covoiturage_id, $nb_places) {
    // NOTE: Pas de verrou optimiste/pessimiste ici (MVP). Un très faible risque de sur-réservation
    // concurrente existe si deux requêtes passent exactement au même instant.
        if ($this->model->participationExiste($user_id, $covoiturage_id)) {
            return ['success' => false, 'message' => 'Vous participez déjà à ce covoiturage'];
        }
        $covoiturage = $this->model->getCovoiturage($covoiturage_id);
        if (!$covoiturage) {
            return ['success' => false, 'message' => 'Covoiturage introuvable'];
        }
        $utilisateur = $this->model->getCredit($user_id);
        $placesPrises = $this->model->getPlacesPrises($covoiturage_id);
    $cout_total = $covoiturage['prix_personne'] * $nb_places;
    // nb_places stocke la capacité initiale; places prises calculées dynamiquement
    $places_restantes = $covoiturage['nb_places'] - $placesPrises['places_prises'];
        if ($utilisateur['credit'] < $cout_total) {
            return ['success' => false, 'message' => 'Crédit insuffisant'];
        }
        if ($places_restantes < $nb_places) {
            return ['success' => false, 'message' => 'Plus assez de places'];
        }
    $pdo = $this->model->getPDO();
        $pdo->beginTransaction();
        try {
            $this->model->ajouterParticipation($user_id, $covoiturage_id, $nb_places);
            $participationId = (int)$pdo->lastInsertId();
            // Plus de décrémentation physique de nb_places : calcul dynamique suffisant
            // Débit immédiat + enregistrement escrow (hold)
            // La table 'covoiturage' utilise 'conducteur_id' (et non 'utilisateur_id')
            $stmtChauff = $pdo->prepare('SELECT conducteur_id FROM covoiturage WHERE covoiturage_id = ?');
            $stmtChauff->execute([$covoiturage_id]);
            $chauffeur_id = (int)$stmtChauff->fetchColumn();
            $escrow = new EscrowService($pdo);
            $hold = $escrow->creerHold($participationId, $user_id, $chauffeur_id, (float)$cout_total);
            if (!$hold['success']) {
                throw new Exception($hold['message'] ?? 'Erreur hold');
            }
            $pdo->commit();
            // Rafraîchir le crédit utilisateur (post-débit) pour retour direct
            $creditStmt = $pdo->prepare('SELECT credit FROM utilisateur WHERE utilisateur_id = ?');
            $creditStmt->execute([$user_id]);
            $creditApres = (float)$creditStmt->fetchColumn();
            return [
                'success' => true,
                'message' => 'Participation enregistrée, fonds bloqués.',
                'participation_id' => $participationId,
                'montant_bloque' => $cout_total,
                'credit_apres' => $creditApres
            ];
        } catch (Exception $e) {
            $pdo->rollback();
            return ['success' => false, 'message' => 'Erreur participation: ' . $e->getMessage()];
        }
    }
}
