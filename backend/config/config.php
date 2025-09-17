<?php
// Configuration des connexions à la base de données

// On charge automatiquement les librairies installées avec Composer (ex : MongoDB)
require_once __DIR__ . '/../../vendor/autoload.php';

// Cette classe permet de se connecter à MySQL ou à MongoDB
class Database {

    // Connexion à la base MySQL (pour toutes les données principales)
    public static function getMySQL() {
        try {
            // On crée une connexion PDO à la base MySQL "ecoride" sur localhost
            $pdo = new PDO(
                'mysql:host=localhost;dbname=ecoride;charset=utf8mb4', // Adresse de la base
                'root', // Nom d'utilisateur
                '',     // Mot de passe (vide par défaut sous XAMPP)
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] // Pour avoir les erreurs en clair
            );
            return $pdo;
        } catch (PDOException $e) {
            // Si la connexion échoue, on affiche un message d'erreur
            die("Erreur MySQL : " . $e->getMessage());
        }
    }

    // Connexion à la collection MongoDB pour les avis utilisateurs
    public static function getMongoAvis() {
        // On crée une connexion à MongoDB sur localhost
        $client = new MongoDB\Client("mongodb://localhost:27017");
        // On retourne la collection "avis" de la base "ecoride_nosql"
        return $client->ecoride_nosql->avis;
    }
}

// Petite classe de configuration générale
// On centralise ici, pour l'instant en dur, la configuration email.
// Amélioration future possible : lire un fichier .env
class AppConfig {
    private static $envLoaded = false;
    private static $env = [];

    // Chargement simple d'un fichier .env (clé=valeur) situé à la racine du projet
    private static function loadEnv() {
        if (self::$envLoaded) return;
        $path = __DIR__ . '/../../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // commentaire
                if (!str_contains($line, '=')) continue;
                [$k,$v] = explode('=', $line, 2);
                self::$env[trim($k)] = trim($v);
            }
        }
        self::$envLoaded = true;
    }

    // Récupère une variable d'environnement sinon valeur par défaut
    private static function env($key, $default = null) {
        self::loadEnv();
        return array_key_exists($key, self::$env) ? self::$env[$key] : $default;
    }

    public static function mail() {
        return [
            'host' => self::env('MAIL_HOST', 'smtp.example.com'),
            'port' => (int) self::env('MAIL_PORT', 587),
            'username' => self::env('MAIL_USERNAME', 'ton_email@example.com'),
            'password' => self::env('MAIL_PASSWORD', 'mot_de_passe'),
            'from_email' => self::env('MAIL_FROM_EMAIL', 'ton_email@example.com'),
            'from_name' => self::env('MAIL_FROM_NAME', 'EcoRide'),
            'dry_run' => strtolower(self::env('MAIL_DRY_RUN', 'true')) === 'true',
        ];
    }
}
?>
