<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/router_guard.php';
require_once dirname(__DIR__, 2) . '/services/AvisMongoService.php';
require_once dirname(__DIR__, 2) . '/services/dto/AvisDtoBuilder.php';
$role = $_SESSION['type_utilisateur'] ?? '';
if (!in_array($role, ['employe','employee','administrateur'])) { eco_json_error('Accès refusé : réservé aux employés / admin.', 403); }
try {
    $avisCollection = Database::getMongoAvis();
    $service = new AvisMongoService($avisCollection);
    $avisCursor = $service->getAvisEnAttente();
    $pdo = Database::getMySQL();
    $builder = new AvisDtoBuilder();

    // MIGRATION: on matérialise le curseur Mongo en tableau pour double parcours (collecte IDs puis enrichissement)
    $avisList = [];
    foreach ($avisCursor as $row) { $avisList[] = $row; }

    // Hydratation auteurs (nom, prenom, pseudo, email) afin d'éviter fallback '#ID' et email null
    $authorIds = [];
    foreach ($avisList as $row) {
        $id = $row['auteur_id'] ?? $row['utilisateur_id'] ?? null;
        if ($id !== null) { $authorIds[(int)$id] = true; }
    }
    $usersById = [];
    if (!empty($authorIds)) {
        // Construction dynamique des placeholders
        $placeholders = implode(',', array_fill(0, count($authorIds), '?'));
        $stmtUsers = $pdo->prepare("SELECT utilisateur_id, pseudo, email, nom, prenom FROM utilisateur WHERE utilisateur_id IN ($placeholders)");
        $stmtUsers->execute(array_keys($authorIds));
        while ($u = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
            $usersById[(int)$u['utilisateur_id']] = $u;
        }
    }

    $enriched = [];
    foreach ($avisList as $a) {
        // Normalisation _id Mongo → string
        try { if (isset($a['_id'])) { $class = 'MongoDB\\BSON\\ObjectId'; if (class_exists($class) && $a['_id'] instanceof $class) { $a['_id'] = (string)$a['_id']; } } } catch (Throwable $t) {}

        // Injection des infos auteur récupérées SQL si disponibles (sans écraser valeurs déjà présentes)
        $injectId = $a['auteur_id'] ?? $a['utilisateur_id'] ?? null;
        if ($injectId !== null) {
            $u = $usersById[(int)$injectId] ?? null;
            if ($u) {
                // On n'écrase que si absent pour conserver éventuelles valeurs spécifiques Mongo
                if (empty($a['utilisateur_prenom']) && !empty($u['prenom'])) { $a['utilisateur_prenom'] = $u['prenom']; }
                if (empty($a['utilisateur_nom']) && !empty($u['nom'])) { $a['utilisateur_nom'] = $u['nom']; }
                if (empty($a['utilisateur_pseudo']) && !empty($u['pseudo'])) { $a['utilisateur_pseudo'] = $u['pseudo']; }
                if (empty($a['utilisateur_email']) && !empty($u['email'])) { $a['utilisateur_email'] = $u['email']; }
            }
        }

        $incidentRow = null;
        if (!empty($a['incident'])) {
            // On conserve uniquement la récupération SQL pour enrichir le DTO (plus de champs legacy tag_incident / incident_statut)
            $stmt = $pdo->prepare('SELECT incident_id AS id, statut FROM incident WHERE covoiturage_id = ? AND utilisateur_id = ? ORDER BY incident_id DESC LIMIT 1');
            $stmt->execute([(int)($a['covoiturage_id'] ?? 0), (int)($a['utilisateur_id'] ?? 0)]);
            $incidentRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $enriched[] = $builder->buildPending($a, $incidentRow);
    }

    // Tri identique legacy : incidents d'abord
    usort($enriched, function($x,$y){ $a=!empty($x['incident']['present']); $b=!empty($y['incident']['present']); return $a===$b?0:($a?-1:1); }); // legacy tri

    // Log debug unique (peut être retiré après validation manuelle)
    EcoLogger::log('avis_debug', 'fetch_pending v1 count=' . count($enriched));

    eco_json_success([
        'schema_version' => 2,
        'avis' => array_map(function($a){
            // Forcer actions.validate/refuse si absent (ancienne buildPending v1)
            if (!isset($a['actions']) && isset($a['moderation'])) {
                $a['actions'] = [
                    'validate' => $a['moderation']['canValidate'] ?? true,
                    'refuse' => $a['moderation']['canRefuse'] ?? true
                ];
            }
            // Nettoyage clés de transition éventuelles
            return $a;
        }, $enriched)
    ]);
} catch (Exception $e) { eco_json_error('Erreur lors de la récupération des avis en attente', 500); }
