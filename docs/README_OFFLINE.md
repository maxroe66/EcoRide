# Guide hors‑ligne (ZIP jury)

Ce document explique comment tester EcoRide sans connexion Internet à partir d’un ZIP.

## Contenu recommandé du ZIP
- Le dossier du projet complet
- Le dossier `vendor/` (déjà installé)
- Un fichier `.env` de démo (copie de `.env.example` avec `MAIL_DRY_RUN=true`)
- Les fichiers SQL: `backend/database.sql` et `backend/seed_demo_ready.sql`

## Étapes (Windows/XAMPP)
1. Copier le dossier `EcoRide` dans `c:\xampp\htdocs\`
2. Démarrer Apache et MySQL dans XAMPP
3. Créer la base `ecoride` et importer `backend/database.sql`
4. (Optionnel) Importer `backend/seed_demo_ready.sql`
5. Vérifier `backend/config/config.php` (host/user/password)
6. Ouvrir: `http://localhost/EcoRide/front_end/html/accueil.html`
7. API de test: `http://localhost/EcoRide/api/ping`

## Remarques
- Si MongoDB n’est pas disponible, seules les fonctionnalités MySQL seront testables.
- Les emails ne partent pas en réel si `MAIL_DRY_RUN=true`; consultez `backend/logs/mail_test.log`.
