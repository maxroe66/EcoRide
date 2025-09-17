<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/models/Incident.php';
eco_require_role(['employe','administrateur']);
try {
    $pdo = Database::getMySQL();
    $incidentModel = new Incident($pdo);
    if (method_exists($incidentModel,'ensureTablePublic')) { $incidentModel->ensureTablePublic(); }
    // On récupère TOUS les incidents, sans filtrer sur le statut
    $rows = $pdo->query("SELECT * FROM incident ORDER BY date_creation DESC")->fetchAll(PDO::FETCH_ASSOC);
    $data = [];
    foreach ($rows as $r) {
        $stmt = $pdo->prepare("SELECT c.lieu_depart,c.lieu_arrivee,c.date_depart,c.heure_depart,c.heure_arrivee,c.conducteur_id,c.covoiturage_id FROM covoiturage c WHERE c.covoiturage_id = ? LIMIT 1");
        $stmt->execute([$r['covoiturage_id']]);
        $cov = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmtP = $pdo->prepare("SELECT p.participation_id,p.utilisateur_id,p.nb_places,e.statut AS escrow_statut,e.montant_brut,e.commission FROM participation p LEFT JOIN escrow_transaction e ON e.participation_id=p.participation_id WHERE p.participation_id=? LIMIT 1");
        $stmtP->execute([$r['participation_id']]);
        $part = $stmtP->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmtU = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1");
        $stmtU->execute([$r['utilisateur_id']]);
        $passager = $stmtU->fetch(PDO::FETCH_ASSOC) ?: [];
        $chauffeur = [];
        if (!empty($cov['conducteur_id'])) { $stmtCh = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id=? LIMIT 1"); $stmtCh->execute([$cov['conducteur_id']]); $chauffeur = $stmtCh->fetch(PDO::FETCH_ASSOC) ?: []; }
        $data[] = [
            'incident_id' => (int)$r['incident_id'],
            'description' => $r['description'],
            'date_creation' => $r['date_creation'],
            'statut' => $r['statut'],
            'covoiturage' => [
                'id' => (int)($cov['covoiturage_id'] ?? 0),
                'lieu_depart' => $cov['lieu_depart'] ?? null,
                'lieu_arrivee' => $cov['lieu_arrivee'] ?? null,
                'date_depart' => $cov['date_depart'] ?? null,
                'heure_depart' => $cov['heure_depart'] ?? null,
                'heure_arrivee' => $cov['heure_arrivee'] ?? null
            ],
            'participation' => (function($part){ $mb = isset($part['montant_brut']) ? (float)$part['montant_brut'] : null; $com = isset($part['commission']) ? (float)$part['commission'] : null; $net = ($mb !== null && $com !== null) ? max(0, $mb - $com) : null; return [ 'participation_id' => (int)($part['participation_id'] ?? 0), 'nb_places' => (int)($part['nb_places'] ?? 0), 'escrow_statut' => $part['escrow_statut'] ?? 'pending', 'montant_brut' => $mb, 'montant_plateforme' => $com, 'montant_chauffeur' => $net ]; })($part),
            'passager' => [ 'id' => (int)($passager['utilisateur_id'] ?? 0), 'pseudo' => $passager['pseudo'] ?? null, 'email' => $passager['email'] ?? null, 'nom' => $passager['nom'] ?? null, 'prenom' => $passager['prenom'] ?? null ],
            'chauffeur' => [ 'id' => (int)($chauffeur['utilisateur_id'] ?? 0), 'pseudo' => $chauffeur['pseudo'] ?? null, 'email' => $chauffeur['email'] ?? null, 'nom' => $chauffeur['nom'] ?? null, 'prenom' => $chauffeur['prenom'] ?? null ]
        ];
    }
    eco_json_success(['incidents' => $data]);
} catch (Exception $e) { eco_json_error('Erreur récupération incidents', 500); }
