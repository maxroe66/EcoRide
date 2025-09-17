<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AvisMongoService.php';
require_once dirname(__DIR__, 2) . '/services/dto/AvisDtoBuilder.php';
require_once dirname(__DIR__, 2) . '/models/Utilisateur.php';
try {
    $avisCollection = Database::getMongoAvis();
    $service = new AvisMongoService($avisCollection);
    $avisRaw = $service->getAvisValides();
    // Hydratation auteurs (réutilise stratégie similaire à en-attente): collecte IDs puis requête IN
    $ids = [];
    foreach ($avisRaw as $d) {
        $aid = $d['auteur_id'] ?? $d['utilisateur_id'] ?? null;
        if ($aid !== null) { $ids[] = (int)$aid; }
    }
    $usersById = [];
    if ($ids) {
        $ids = array_values(array_unique(array_filter($ids, fn($v)=>$v!==null)));
        if ($ids) {
            $pdo = Database::getMySQL();
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT utilisateur_id, nom, prenom, pseudo, email FROM utilisateur WHERE utilisateur_id IN ($in)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $usersById[(int)$u['utilisateur_id']] = $u;
            }
        }
    }
    $builder = new AvisDtoBuilder();
    $enriched = [];
    foreach ($avisRaw as $d) {
        // injecter champs utilisateur hydratés dans la copie avant build
        $copy = $d instanceof \MongoDB\Model\BSONDocument ? $d->getArrayCopy() : (array)$d;
        $aid = $copy['auteur_id'] ?? $copy['utilisateur_id'] ?? null;
        if ($aid !== null && isset($usersById[(int)$aid])) {
            $u = $usersById[(int)$aid];
            $copy['utilisateur_nom'] = $u['nom'] ?? null;
            $copy['utilisateur_prenom'] = $u['prenom'] ?? null;
            $copy['utilisateur_pseudo'] = $u['pseudo'] ?? null;
            $copy['utilisateur_email'] = $u['email'] ?? null;
        }
        $enriched[] = $builder->buildPublic($copy);
    }
    eco_json_success(['avis' => $enriched, 'schema_version' => 1]);
} catch (Exception $e) {
    eco_json_error('Erreur lors de la récupération des avis validés', 500);
}
