<?php
use MongoDB\BSON\ObjectId;
// Modèle pour l'ajout d'avis dans MongoDB
class AvisMongo {
    private $avisCollection;
    public function __construct($avisCollection) { $this->avisCollection = $avisCollection; }
    public function ajouter($avis) {
        return $this->avisCollection->insertOne($avis);
    }

    // Récupérer les avis par statut
    public function getAvisByStatut($statut) {
        return $this->avisCollection->find(['statut' => $statut])->toArray();
    }

    // Met à jour le statut d'un avis par son _id
    public function updateStatutAvis($avisId, $statut) {
        $fromMongoId = is_string($avisId) ? new ObjectId($avisId) : $avisId;
        $result = $this->avisCollection->updateOne(
            ['_id' => $fromMongoId],
            ['$set' => ['statut' => $statut]]
        );
        file_put_contents(__DIR__ . '/../logs/avis_debug.log', date('c') . " | updateStatutAvis id=".json_encode($avisId)." mongoId=".json_encode($fromMongoId)." matched=".$result->getMatchedCount()." modified=".$result->getModifiedCount().PHP_EOL, FILE_APPEND);
        // Succès si le document existe (matchedCount > 0), même si pas modifié
        return $result->getMatchedCount() > 0;
    }
}
