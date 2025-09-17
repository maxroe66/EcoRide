<?php
// Modèle très simple pour gérer les incidents signalés (trajets mal passés)
// on crée la table si elle n'existe pas.

class Incident {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }
    // La table est maintenant créée via database.sql ; méthode conservée pour compatibilité mais vide.
    public function ensureTablePublic(): void { /* no-op (table statique) */ }

    public function creer($participation_id, $covoiturage_id, $utilisateur_id, $description) {
        // suppose table déjà assurée hors transaction
        $stmt = $this->pdo->prepare("INSERT INTO incident (participation_id, covoiturage_id, utilisateur_id, description) VALUES (?,?,?,?)");
        $ok = $stmt->execute([(int)$participation_id, (int)$covoiturage_id, (int)$utilisateur_id, $description]);
        return $ok ? $this->pdo->lastInsertId() : false;
    }

    public function get($incident_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM incident WHERE incident_id = ? LIMIT 1");
        $stmt->execute([(int)$incident_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function marquerResolue($incident_id) {
        $stmt = $this->pdo->prepare("UPDATE incident SET statut='resolu' WHERE incident_id = ?");
        return $stmt->execute([(int)$incident_id]);
    }

    public function getEnCours() {
        $stmt = $this->pdo->query("SELECT * FROM incident WHERE statut='en_cours' ORDER BY date_creation DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>