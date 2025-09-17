<?php
// Façade unifiant l'accès aux avis (Mongo + fallback MySQL) et calculs agrégés
require_once __DIR__ . '/../models/AvisFallback.php';
require_once __DIR__ . '/AvisMongoService.php';

class AvisFacadeService {
    private PDO $pdo;
    private $mongoCollection; // nullable

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->mongoCollection = $this->initMongo();
    }

    private function initMongo() {
        try { return Database::getMongoAvis(); } catch (Throwable $t) { return null; }
    }

    /**
     * Ajoute un avis en statut pending (Mongo si dispo, sinon fallback direct)
     */
    public function ajouterAvisPending(int $userId, int $covoiturageId, ?int $note, string $commentaire, array $extra = []): array {
        if ($covoiturageId <= 0) return ['success'=>false,'message'=>'Covoiturage invalide'];
        if ($note !== null && ($note < 1 || $note > 5)) { return ['success'=>false,'message'=>'Note invalide']; }
        $commentaire = trim($commentaire);
        if ($commentaire === '') { return ['success'=>false,'message'=>'Commentaire requis']; }
        // Empêcher doublon
        if ($this->mongoCollection) {
            try {
                $ex = $this->mongoCollection->findOne(['utilisateur_id'=>$userId,'covoiturage_id'=>$covoiturageId]);
                if ($ex) { return ['success'=>false,'message'=>'Avis déjà déposé']; }
            } catch (Exception $e) {}
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM avis_fallback WHERE utilisateur_id = ? AND covoiturage_id = ? LIMIT 1');
            $stmt->execute([$userId, $covoiturageId]);
            if ($stmt->fetch()) { return ['success'=>false,'message'=>'Avis déjà déposé (fallback).']; }
        }
        // Construction doc
        $dateCreation = (function(){ $class='MongoDB\\BSON\\UTCDateTime'; return class_exists($class)? new $class(): (new DateTime())->format(DateTime::ATOM); })();
        $doc = [
            'utilisateur_id'=>$userId,
            'covoiturage_id'=>$covoiturageId,
            'note'=>$note,
            'commentaire'=>$commentaire,
            'date_creation'=>$dateCreation,
            'statut'=>'pending'
        ] + $extra;
        if ($this->mongoCollection) {
            try {
                $service = new AvisMongoService($this->mongoCollection);
                $res = $service->ajouterAvis($doc);
                return ['success'=>true,'mongo'=>true,'inserted_id'=>$res['inserted_id'] ?? null];
            } catch (Exception $e) {
                // fallback silencieux
            }
        }
        // Fallback
        $fallback = new AvisFallback($this->pdo);
        $ok = $fallback->inserer($userId, $covoiturageId, $note ?? 0, $commentaire);
        if ($ok) return ['success'=>true,'fallback'=>true];
        return ['success'=>false,'message'=>'Impossible d\'enregistrer'];
    }

    /**
     * Retourne avis (fusion) pour un covoiturage + moyenne locale trajet
     */
    public function listerAvisTrajet(int $covoiturageId, int $limit = 5): array {
        $limit = max(1, min(50, $limit));
        $avisMongo = [];
        if ($this->mongoCollection) {
            try {
                $cursor = $this->mongoCollection->find(['covoiturage_id'=>$covoiturageId,'statut'=>'approved'], ['limit'=>$limit,'sort'=>['date_creation'=>-1]]);
                foreach ($cursor as $doc) { $avisMongo[] = $this->mapDoc($doc); }
            } catch (Exception $e) {}
        }
        // Fallback
        $stmt = $this->pdo->prepare('SELECT a.note, a.commentaire, a.date_creation, u.prenom, u.nom FROM avis_fallback a JOIN utilisateur u ON a.utilisateur_id=u.utilisateur_id WHERE a.covoiturage_id=? AND a.note BETWEEN 1 AND 5 ORDER BY a.date_creation DESC LIMIT ?');
        $stmt->bindValue(1, $covoiturageId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $avisSQL = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $avisSQL[] = [
                'author'=>($r['prenom'] ?? '') . ' ' . (isset($r['nom'])? strtoupper(substr($r['nom'],0,1)).'.':''),
                'rating'=>(int)$r['note'],
                'text'=>$r['commentaire'] ?? ''
            ];
        }
        $fusion = array_slice(array_merge($avisMongo, $avisSQL), 0, $limit);
        $moy = 0.0; $cnt=0; foreach ($fusion as $a){ $v=(int)$a['rating']; if ($v>=1 && $v<=5){ $moy += $v; $cnt++; } }
        $moyenne = $cnt>0 ? round($moy/$cnt,1) : 0.0;
        return ['success'=>true,'avis'=>$fusion,'moyenne_trajet'=>$moyenne];
    }

    /**
     * Statistiques globales conducteur (moyenne tous trajets via fallback principal)
     */
    public function statsConducteur(int $conducteurId): array {
        $stmt = $this->pdo->prepare("SELECT ROUND(AVG(a.note),1) FROM avis_fallback a JOIN covoiturage c ON a.covoiturage_id=c.covoiturage_id WHERE c.conducteur_id = ? AND a.note BETWEEN 1 AND 5");
        $stmt->execute([$conducteurId]);
        $val = $stmt->fetchColumn();
        return ['success'=>true,'moyenne_globale'=>$val !== false && $val !== null ? (float)$val : 0.0];
    }

    /**
     * Réplication fallback quand un avis Mongo validé contient une note (utilisé par valider_avis)
     */
    public function replicationFallbackSiValide($avisDocument): void {
        if (!$avisDocument || !isset($avisDocument['note'])) return;
        $covoitId = (int)($avisDocument['covoiturage_id'] ?? 0);
        $userId = (int)($avisDocument['utilisateur_id'] ?? 0);
        $note = (int)$avisDocument['note'];
        if ($covoitId <=0 || $userId <=0 || $note <1 || $note>5) return;
        try {
            $stmtCheck = $this->pdo->prepare('SELECT 1 FROM avis_fallback WHERE utilisateur_id=? AND covoiturage_id=? LIMIT 1');
            $stmtCheck->execute([$userId, $covoitId]);
            if ($stmtCheck->fetch()) return;
            $stmtIns = $this->pdo->prepare('INSERT INTO avis_fallback(utilisateur_id, covoiturage_id, note, commentaire) VALUES (?,?,?,?)');
            $stmtIns->execute([$userId, $covoitId, $note, (string)($avisDocument['commentaire'] ?? '')]);
        } catch (Exception $e) {
            EcoLogger::log('avis_replication', 'err='.$e->getMessage());
        }
    }

    private function mapDoc($doc): array {
        $prenom = $doc['utilisateur_prenom'] ?? '';
        $nom = $doc['utilisateur_nom'] ?? '';
        $author = $prenom . ' ' . ($nom ? strtoupper(substr($nom,0,1)).'.' : '');
        return [
            'author'=>$author,
            'rating'=>isset($doc['note']) ? (int)$doc['note'] : 0,
            'text'=>$doc['commentaire'] ?? ''
        ];
    }
}
