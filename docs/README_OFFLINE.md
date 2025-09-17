# EcoRide — Guide OFFLINE (ZIP jury)

Ce document s’adresse au jury (ou à une relecture hors-ligne) lorsque vous utilisez l’archive ZIP fournie.

## Contenu de l’archive
- Code de l’application
- Dossier `vendor/` (dépendances PHP déjà installées)
- Fichier `.env` de démonstration (sans secrets réels, `MAIL_DRY_RUN=true`)
- Scripts SQL: `backend/database.sql` et `backend/seed_demo.sql` (éventuellement `backend/seed_demo_ready.sql`)

## Prérequis
- Windows + XAMPP (Apache + MySQL)
- PHP 8+ (inclus dans XAMPP)
- MongoDB Community Server (localhost:27017)

## Installation (offline)
1. Dézippez le dossier dans `c:\xampp\htdocs\EcoRide`
2. Démarrez Apache et MySQL (XAMPP Control Panel)
3. Dans phpMyAdmin, créez la base `ecoride` puis importez `backend/database.sql`
4. (Optionnel) Importez `backend/seed_demo_ready.sql` (ou `backend/seed_demo.sql`)
5. Vérifiez que `mod_rewrite` est actif et que `public/.htaccess` est pris en compte (si 404 côté API)
6. Front: `http://localhost/EcoRide/front_end/html/accueil.html`
7. API: `http://localhost/EcoRide/public/`

## Notes
- Les emails sont en mode démonstration (`MAIL_DRY_RUN=true`)
- Ne pas utiliser ce `.env` pour un environnement réel (aucun secret sensible)

(c) Projet pédagogique – ECF EcoRide
