# 🚗💚 EcoRide — Guide de déploiement local (ECF)

Ce dépôt contient l’application EcoRide. Cette page couvre l’installation locale rapide exigée pour l’ECF. La documentation détaillée reste disponible dans `docs/` (liens en bas).

## Prérequis
- Windows + XAMPP (Apache + MySQL/MariaDB)
- PHP 8+ (inclus dans XAMPP)
- Composer (gestion des dépendances PHP)
- MongoDB Community Server (si vous voulez tester les avis NoSQL)
  - Et l’extension PHP MongoDB: `php_mongodb` activée dans `php.ini`

## Installation rapide (Windows/XAMPP)
1. Placez le dossier du projet dans `c:\xampp\htdocs\EcoRide`
2. Démarrez Apache et MySQL depuis XAMPP
3. Créez la base `ecoride` puis importez `backend/database.sql` via phpMyAdmin
4. Vérifiez la connexion DB dans `backend/config/config.php` (host/user/password)
5. Installez les dépendances Composer à la racine du projet:
   - PowerShell: `composer install`
6. (Optionnel) Importez des données de démo:
   - Soit `backend/seed_demo_ready.sql` (généré via le script ci-dessous)
   - Soit `backend/seed_demo.sql` après avoir remplacé les `<HASH_...>` par des bcrypt
7. Ouvrez le front: `http://localhost/EcoRide/front_end/html/accueil.html`

### Générer le seed prêt à l’emploi (optionnel)
Dans PowerShell à la racine du projet:
```
php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql
```
Importez ensuite `backend/seed_demo_ready.sql` dans phpMyAdmin.

## API (backend)
L’API est servie via `public/index.php`. Assurez-vous que:
- Apache mod_rewrite est activé
- Les `.htaccess` sont autorisés (AllowOverride All)

URLs de test:
- Base: `http://localhost/EcoRide/api`
- GET `http://localhost/EcoRide/api/ping`
- GET `http://localhost/EcoRide/api/csrf-token`

## Fichier .env (mails)
Créez un fichier `.env` (copie de `.env.example`) si vous souhaitez tester l’envoi d’emails. Avec `MAIL_DRY_RUN=true`, aucun email réel n’est envoyé: un log est écrit dans `backend/logs/mail_test.log`.

## Workflow Git (ECF)
- Branche principale: `main` (stable)
- Branche d’intégration: `develop`
- Nouvelles fonctionnalités: `feature/<nom>` → PR vers `develop`
- Release: `develop` → PR vers `main`
Les branches protégées exigent une PR et vérifications.

## Dépannage
- 404/500 API: vérifiez `mod_rewrite` et l’accès via `http://localhost/EcoRide/api/...`
- Erreur MongoDB: installez MongoDB et activez `php_mongodb` dans `php.ini`
- Erreur Composer: installez Composer et exécutez `composer install` à la racine

## Liens documentation
- Doc complète: `docs/README_Complet.md`
- Manuel d’utilisation: `docs/Manuel_utilisation_EcoRide.md`
- Soutenance: `docs/README_Soutenance.md`

---
(c) Projet pédagogique – ECF EcoRide

