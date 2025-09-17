<?php
// Génère un fichier seed prêt (SQL) en remplaçant les placeholders <HASH_...>
// Usage (Windows PowerShell à la racine du projet):
//   php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql

// Mots de passe de démo (ne pas utiliser en prod)
$passwords = [
    'HASH_ADMIN'  => 'Admin@123!',
    'HASH_EMP'    => 'Employe@123!',
    'HASH_DRIVER' => 'Driver@123!',
    'HASH_PASS'   => 'Passenger@123!',
];

$templatePath = __DIR__ . '/../seed_demo.sql';
if (!is_file($templatePath)) {
    fwrite(STDERR, "Template introuvable: $templatePath\n");
    exit(1);
}

$sql = file_get_contents($templatePath);
foreach ($passwords as $key => $plain) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $sql = str_replace('<' . $key . '>', $hash, $sql);
}

// Ajout d’un en-tête informatif
$header = "-- Fichier généré automatiquement par scripts/generate_seed.php\n"
        . "-- Contient des hash bcrypt pour les mots de passe de démo.\n"
        . "-- Comptes:\n"
        . "--   admin@ecoride.test / Admin@123!\n"
        . "--   employe@ecoride.test / Employe@123!\n"
        . "--   driver@ecoride.test / Driver@123!\n"
        . "--   passenger@ecoride.test / Passenger@123!\n\n";

echo $header . $sql;
