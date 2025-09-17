<?php
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { exit; }
$pdo = Database::getMySQL();
$covoiturage_id = isset($_GET['covoiturage_id']) ? (int)$_GET['covoiturage_id'] : 0;
if ($covoiturage_id <= 0) { eco_json_error('Paramètre covoiturage_id manquant ou invalide', 400); }
try {
    $stmt = $pdo->prepare('SELECT conducteur_id FROM covoiturage WHERE covoiturage_id = ? LIMIT 1');
    $stmt->execute([$covoiturage_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { eco_json_error('Covoiturage introuvable', 404); }
    $conducteur_id = (int)$row['conducteur_id'];
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
    $sql = "SELECT a.note, a.commentaire, a.date_creation, u.prenom, u.nom
            FROM avis_fallback a
            JOIN covoiturage c ON a.covoiturage_id = c.covoiturage_id
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
            WHERE c.conducteur_id = ? AND a.note BETWEEN 1 AND 5
            ORDER BY a.date_creation DESC
            LIMIT $limit";
    $stmtAvis = $pdo->prepare($sql);
    $stmtAvis->execute([$conducteur_id]);
    $reviews = [];
    while ($r = $stmtAvis->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'author' => ($r['prenom'] ?? '') . ' ' . (isset($r['nom']) ? strtoupper(substr($r['nom'],0,1)).'.' : ''),
            'rating' => (int)$r['note'],
            'text'   => $r['commentaire'] ?? '',
            'date'   => $r['date_creation']
        ];
    }
    eco_json_success(['reviews' => $reviews, 'count' => count($reviews)]);
} catch (Exception $e) { eco_json_error('Erreur récupération avis chauffeur', 500); }
