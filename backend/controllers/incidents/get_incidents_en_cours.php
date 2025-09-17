<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/models/Incident.php';
require_once dirname(__DIR__, 2) . '/services/dto/IncidentDtoBuilder.php';
eco_require_role(['employe','administrateur']);
try {
    $pdo = Database::getMySQL();
    $incidentModel = new Incident($pdo);
    if (method_exists($incidentModel,'ensureTablePublic')) { $incidentModel->ensureTablePublic(); }
    $rows = $incidentModel->getEnCours();
    $builder = new IncidentDtoBuilder();
    $items = [];
    foreach ($rows as $r) {
        // Récup covoiturage
        $stmtCov = $pdo->prepare("SELECT c.lieu_depart,c.lieu_arrivee,c.date_depart,c.heure_depart,c.heure_arrivee,c.conducteur_id,c.covoiturage_id FROM covoiturage c WHERE c.covoiturage_id = ? LIMIT 1");
        $stmtCov->execute([$r['covoiturage_id']]);
        $cov = $stmtCov->fetch(PDO::FETCH_ASSOC) ?: [];
        // Participation + escrow
        $stmtPart = $pdo->prepare("SELECT p.participation_id,p.utilisateur_id,p.nb_places,e.statut AS escrow_statut,e.montant_brut,e.commission FROM participation p LEFT JOIN escrow_transaction e ON e.participation_id=p.participation_id WHERE p.participation_id=? LIMIT 1");
        $stmtPart->execute([$r['participation_id']]);
        $part = $stmtPart->fetch(PDO::FETCH_ASSOC) ?: [];
        // Passager
        $stmtPass = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1");
        $stmtPass->execute([$r['utilisateur_id']]);
        $passager = $stmtPass->fetch(PDO::FETCH_ASSOC) ?: [];
        // Chauffeur
        $chauffeur = [];
        if (!empty($cov['conducteur_id'])) {
            $stmtCh = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1");
            $stmtCh->execute([$cov['conducteur_id']]);
            $chauffeur = $stmtCh->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        $items[] = $builder->build($r, $cov, $part, $passager, $chauffeur, null);
    }
    eco_json_success([
        'schema_version' => 2,
        'incidents' => $items
    ]);
} catch (Exception $e) { eco_json_error('Erreur récupération incidents', 500); }
