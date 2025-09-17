# 🚗💚 EcoRide — Guide de déploiement local (ECF)

Ce dépôt contient l’application EcoRide. Cette page décrit uniquement la mise en place locale attendue pour l’ECF. La documentation détaillée reste disponible dans `docs/` (liens en bas).

## Prérequis
- Windows + XAMPP (Apache + MySQL/MariaDB)
- PHP 8+ (inclus dans XAMPP)

## Installation rapide (Windows/XAMPP)
1. Placez le dossier du projet dans `c:\xampp\htdocs\EcoRide`
2. Démarrez Apache et MySQL depuis XAMPP
3. Créez la base `ecoride` puis importez `backend/database.sql` via phpMyAdmin
4. Vérifiez la connexion DB dans `backend/config/config.php` (host/user/password)
5. (Optionnel) Importez des données de démo:
   - Soit `backend/seed_demo_ready.sql` (généré via le script ci-dessous)
   - Soit `backend/seed_demo.sql` après avoir remplacé les `<HASH_...>` par des bcrypt
6. Ouvrez le front: `http://localhost/EcoRide/front_end/html/accueil.html`

## Générer le seed prêt à l’emploi (optionnel)
Dans PowerShell à la racine du projet:
```
php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql
```
Importez ensuite `backend/seed_demo_ready.sql` dans phpMyAdmin.

## URLs utiles (smoke test)
- API base: `http://localhost/EcoRide/api`
- GET `http://localhost/EcoRide/api/ping`
- GET `http://localhost/EcoRide/api/csrf-token`

## Mails (optionnel)
Vous pouvez créer un fichier `.env` à la racine pour configurer PHPMailer. Avec `MAIL_DRY_RUN=true`, les emails de démo sont écrits dans `backend/logs/mail_test.log`. Voir détails dans la doc complète.

## Liens documentation
- Doc complète: `docs/README_Complet.md`
- Manuel d’utilisation (PDF/Markdown): `docs/Manuel_utilisation_EcoRide.md`
- Soutenance: `docs/README_Soutenance.md`

---
(c) Projet pédagogique – ECF EcoRide

