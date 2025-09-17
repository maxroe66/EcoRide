<?php
// Modèle de secours pour insérer un avis dans la table SQL 'avis_fallback'.
// La table est désormais définie statiquement dans database.sql (plus de CREATE TABLE dynamique ici).

class AvisFallback {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function inserer($utilisateur_id, $covoiturage_id, $note, $commentaire) {
        // Hypothèse: la migration SQL a déjà créé la table. En cas d'erreur (table manquante),
        // l'exception remontera pour être gérée plus haut (chemin fallback déjà dans un bloc try/catch).
        $stmt = $this->pdo->prepare("INSERT INTO avis_fallback (utilisateur_id, covoiturage_id, note, commentaire) VALUES (?,?,?,?)");
        return $stmt->execute([(int)$utilisateur_id, (int)$covoiturage_id, (int)$note, $commentaire]);
    }
}
?>
