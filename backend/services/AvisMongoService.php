<?php
// Service pour la logique métier liée à l'ajout d'avis MongoDB
require_once __DIR__ . '/../models/AvisMongo.php';
class AvisMongoService {
    // Valider un avis (statut = 'approved')
    public function validerAvis($avisId) {
        return $this->model->updateStatutAvis($avisId, 'approved');
    }

    // Refuser un avis (statut = 'refused')
    public function refuserAvis($avisId) {
        return $this->model->updateStatutAvis($avisId, 'refused');
    }
    // Récupérer tous les avis en attente de validation
    public function getAvisEnAttente() {
        return $this->model->getAvisByStatut('pending');
    }
    private $model;
    public function __construct($avisCollection) { $this->model = new AvisMongo($avisCollection); }
    public function ajouterAvis($avis) {
        // Ajout du statut 'pending' par défaut
        $avis['statut'] = 'pending';
    $res = $this->model->ajouter($avis);
        try {
            $idStr = method_exists($res, 'getInsertedId') ? (string)$res->getInsertedId() : 'unknown_id';
            file_put_contents(__DIR__ . '/../logs/avis_debug.log', date('c') . " | insert_pending id=$idStr doc=" . json_encode($avis) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $t) {}
    return ['success' => true, 'message' => 'Avis ajouté dans MongoDB (en attente de validation)', 'inserted_id' => (isset($res) && method_exists($res,'getInsertedId')) ? (string)$res->getInsertedId() : null];
    }

    // Récupérer tous les avis en attente de validation

    // Récupérer tous les avis validés (pour affichage public)
    public function getAvisValides() {
        return $this->model->getAvisByStatut('approved');
    }
    // Valider un avis (statut = 'approved')

    // Refuser un avis (statut = 'rejected')
}
