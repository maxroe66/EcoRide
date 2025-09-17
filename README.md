# 🚗💚 EcoRide — Guide de déploiement local (ECF)

Ce dépôt contient l’application EcoRide. Cette page décrit uniquement la mise en place locale attendue pour l’ECF. La documentation détaillée reste disponible dans `docs/` (liens en bas).

## Prérequis
- Windows + XAMPP (Apache + MySQL/MariaDB)
- PHP 8+ (inclus dans XAMPP)
- Composer installé (https://getcomposer.org/)
- MongoDB Community Server (localhost:27017) + extension PHP "mongodb"

## Installation rapide (Windows/XAMPP)
1. Placez le dossier du projet dans `c:\\xampp\\htdocs\\EcoRide`
2. Démarrez Apache et MySQL depuis XAMPP
3. Installez les dépendances PHP (à la racine du projet):
   - Exécutez `composer install`
4. Créez la base `ecoride` puis importez `backend/database.sql` via phpMyAdmin
5. (Optionnel) Importez des données de démo:
   - `backend/seed_demo_ready.sql` (généré via le script ci-dessous), OU
   - `backend/seed_demo.sql` (remplacer les `<HASH_...>` par des bcrypt valides)
6. Copiez `.env.example` en `.env` et complétez a minima la section MAIL (laisser `MAIL_DRY_RUN=true` pour la démo). 
   - Variables disponibles: MAIL_*, MYSQL_*, MONGO_URI
7. Vérifiez que `mod_rewrite` est actif et que `.htaccess` est pris en compte (si 404 côté API)
8. Front: `http://localhost/EcoRide/front_end/html/accueil.html`

## Générer le seed prêt à l’emploi (optionnel)
Dans PowerShell à la racine du projet:
```
php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql
```
Importez ensuite `backend/seed_demo_ready.sql` dans phpMyAdmin.

## URLs utiles (smoke test)
- Front: `http://localhost/EcoRide/front_end/html/accueil.html`
- API (via public/.htaccess): `http://localhost/EcoRide/public/`
   - GET `http://localhost/EcoRide/public/api/ping`
   - GET `http://localhost/EcoRide/public/api/csrf-token`

## Mails (optionnel)
Créez un fichier `.env` (copie de `.env.example`) à la racine pour configurer PHPMailer. 
Avec `MAIL_DRY_RUN=true`, les emails de démo sont enregistrés en log (voir doc complète). 
Ne commitez jamais de `.env` (déjà ignoré par `.gitignore`).

## MongoDB (NoSQL)
Installez MongoDB Community Server et assurez-vous que le service est lancé sur `mongodb://localhost:27017`. 
Activez l’extension PHP `mongodb` si nécessaire. Les avis (NoSQL) utilisent MongoDB.

## Workflow Git (exigence ECF)
- main: stable; develop: intégration; feature/*: branches de fonctionnalités depuis develop
- PR feature → develop (1 review minimum), puis PR “release” develop → main
- Branches protégées activées (PR obligatoires, pas de force-push, historique linéaire)

## Liens documentation
- Doc complète: `docs/README_Complet.md`
- Manuel d’utilisation (PDF/Markdown): `docs/Manuel_utilisation_EcoRide.md`
- Soutenance: `docs/README_Soutenance.md`

## Dépannage rapide
- `vendor/autoload.php` introuvable → exécuter `composer install` à la racine
- 404 API → activer `mod_rewrite` (Apache) et vérifier `public/.htaccess`
- Erreur MySQL → vérifier base `ecoride`, user/mot de passe (XAMPP: root sans mot de passe)
- Erreur Mongo → démarrer MongoDB (`localhost:27017`) et activer l’extension PHP

---
(c) Projet pédagogique – ECF EcoRide

