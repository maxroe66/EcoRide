# 🚗💚 EcoRide — Plateforme de Covoiturage Écologique (Complet)

> Projet pédagogique (ECF) — Application web de covoiturage responsable, avec API PHP, base MySQL et usage NoSQL pour les avis.

![EcoRide](../front_end/icons_images/ecorideicon-removebg-preview.png)

## 1) Aperçu

EcoRide permet de:
- Rechercher des covoiturages par ville + date, avec filtres (prix max, écologique si voiture électrique, etc.)
- S’inscrire / se connecter (20 crédits offerts à l’inscription)
- Devenir chauffeur, ajouter un véhicule, créer un trajet
- Participer à un trajet (gestion des places, crédits et commission)
- Gérer le cycle de vie d’un trajet (démarrer, terminer, annuler), incidents et avis
- Modération (employé) et administration (statistiques, suspension compte)

Architecture: backend exposé en API JSON stateless; front en HTML/CSS/JS côté client.

## 2) Stack technique
- Front: HTML5, CSS3, JavaScript (ES6) sans framework
- Back: PHP 8+ avec PDO (MySQL)
- BDD relationnelle: MySQL/MariaDB (XAMPP conseillé)
- BDD NoSQL: MongoDB (stockage des avis) avec fallback SQL
- Mail: PHPMailer via `MailService` (mode dry_run par défaut)

## 3) Structure du projet (extrait)
```
backend/
  config/config.php        # Connexions DB + lecture .env
  controllers/             # Contrôleurs classés par domaine
  includes/bootstrap.php   # Sessions, CSRF, helpers JSON, logger
  routes/                  # Front controller & table des routes
  services/                # Logique métier (crédits, covoiturage, avis, incidents...)
  models/                  # Accès aux données (PDO) & entités simples
  emails/templates/        # Templates HTML des mails (annulation/fin)
  database.sql             # Schéma SQL relationnel
front_end/
  html/ css/ js/           # Pages et scripts front
public/
  index.php                # Front controller des routes /api
  .htaccess                # Réécriture vers index.php (API)
docs/
  README_Soutenance.md, api_contracts_v1.md, diagrammes/, charte-graphique/
```

Pourquoi pas de dossier `views/` côté PHP ? Tout le rendu se fait côté client. Le backend ne renvoie que du JSON.

## 4) Routing et API
- Toutes les routes d’API passent par `public/index.php` + `backend/routes/Router.php`
- Préfixe d’API par défaut en local (XAMPP): `http://localhost/EcoRide/api`
- `.htaccess` redirige toute URL non-fichier vers `index.php`

Exemples:
- GET `/api/ping` → diagnostic routeur
- GET `/api/csrf-token` → fournit le jeton CSRF
- GET `/api/covoiturages?ville=Paris&date=2025-09-20` → recherche
- POST `/api/auth/register` → inscription (CSRF requis)

Voir la table complète dans `backend/routes/routes.php`.

## 5) Installation (Windows + XAMPP)
1. Cloner ce dépôt et placer le dossier dans `c:\xampp\htdocs\EcoRide`
2. Démarrer Apache + MySQL dans XAMPP
3. Créer la base MySQL `ecoride` et importer `backend/database.sql` (via phpMyAdmin)
4. Configurer si besoin `backend/config/config.php` (host/user/password)
5. (Optionnel) Installer MongoDB localement (sinon le fallback SQL pour les avis est utilisé)
6. (Optionnel) Créer un fichier `.env` à la racine pour la configuration SMTP (voir section Mail)
7. Ouvrir le front: `http://localhost/EcoRide/front_end/html/accueil.html`

Smoke test rapide (navigateur):
- `http://localhost/EcoRide/api/ping`
- `http://localhost/EcoRide/api/csrf-token`

## 6) Configuration mails (.env)
Créer un fichier `.env` (à la racine du projet) pour PHPMailer:
```
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=XXXXXX
MAIL_PASSWORD=YYYYYY
MAIL_FROM_EMAIL=no-reply@ecoride.test
MAIL_FROM_NAME=EcoRide
MAIL_DRY_RUN=true
```
Avec `MAIL_DRY_RUN=true`, les emails ne sont pas envoyés: ils sont écrits dans `backend/logs/mail_test.log` (pratique pour la démo).

## 7) Données de démo (recommandé)
Inscription via l’UI pour créer un utilisateur standard (20 crédits). Pour créer rapidement un administrateur et un employé:

1) Générer un hash de mot de passe (PowerShell):
```powershell
php -r "echo password_hash('Admin@123!', PASSWORD_DEFAULT), PHP_EOL;"
```
2) Insérer en SQL (remplacer <HASH> par la valeur générée):
```sql
INSERT INTO utilisateur (nom, prenom, email, pseudo, password, credit, type_utilisateur)
VALUES ('Admin','Demo','admin@ecoride.test','AdminDemo','<HASH>', 100, 'administrateur');

INSERT INTO utilisateur (nom, prenom, email, pseudo, password, credit, type_utilisateur)
VALUES ('Employe','Demo','employe@ecoride.test','EmployeDemo','<HASH>', 50, 'employe');
```
Ajoutez ensuite au moins 1 véhicule électrique et 1 trajet pour une démo fluide.

Vous pouvez également importer le script `backend/seed_demo.sql` dans phpMyAdmin pour peupler automatiquement:
- 1 administrateur, 1 employé
- 1 chauffeur et 1 passager
- 2 marques, 2 véhicules (électrique + thermique)
- 2 covoiturages planifiés

Remplacez les `<HASH_...>` par des hash générés (voir commande ci-dessus) avant import.

Alternative rapide (génération automatique):
```
php backend\scripts\generate_seed.php > backend\seed_demo_ready.sql
```
Puis importez `backend/seed_demo_ready.sql` directement dans phpMyAdmin (les hash sont déjà calculés).

## 8) Sécurité (synthèse)
- CSRF: jeton généré en session (voir `backend/includes/bootstrap.php`), exposé via `/api/csrf-token`, vérifié par `eco_verify_csrf()` sur les POST
- Sessions & rôles: accès protégé via `auth` et `role:...` dans `routes.php`
- PDO préparé: toutes les requêtes utilisent des paramètres liés (anti‑injection)
- Garde routeur: accès direct aux contrôleurs bloqué (réponse JSON)
- Réponses JSON sobres et cohérentes (option format v2)
- Journalisation simple (fichiers dans `backend/logs/`)

Côté front (`front_end/js/api/ecoApi.js`): un client API centralisé gère automatiquement le CSRF, JSON vs FormData, retry 403 CSRF.

## 9) Emails (annulation/fin de trajet)
- Templates HTML: `backend/emails/templates/`
- Service: `backend/services/MailService.php`
- Démo: laisser `MAIL_DRY_RUN=true` et montrer `backend/logs/mail_test.log`

## 10) Docs utiles
- `docs/README_Soutenance.md` (sécurité front/back, éléments de soutenance)
- `docs/api_contracts_v1.md` (contrats d’API v1)
- `docs/diagrammes/` (cas d’usage, classes, séquences)
- `docs/charte-graphique/` (palette, maquettes)

Formulation utile devant le jury:
> “Pour l’ECF, j’ai priorisé la couverture des US et la sécurité. La vérification s’est faite par parcours manuel reproductible et requêtes API préparées. L’architecture est prête pour des tests automatisés (e2e/API), que je placerais en prochaine étape.”

## 11 bis) Démo pas‑à‑pas (3–4 minutes)
1. Ouvrir `front_end/html/accueil.html` (US1–US2)
2. Lancer une recherche dans `covoiturage.html` (US3–US4) → voir un trajet “électrique”
3. Ouvrir un détail `covoiturage-detail.html` (US5)
4. Se connecter (admin ou user de démo) puis aller dans `espace-utilisateur.html` et `ajouter-covoiturage.html` (US8–US9)
5. Montrer `admin.html` / `employe.html` pour stats / modération (US12–US13)
6. (Option) Montrer les endpoints API dans le navigateur:
  - `/api/ping` (diag)
  - `/api/csrf-token`
  - `/api/covoiturages?ville=Paris&date=2025-09-20`

## 11) Contribution (workflow simple)
- Branche principale: `main`
- Créez des branches de fonctionnalité: `feature/usX_description`
- Commits: `feat:`, `fix:`, `docs:`, `refactor:`

## 12) Dépannage rapide
- 404/500 sur l’API: vérifiez que vous accédez via `http://localhost/EcoRide/api/...` et que `.htaccess` est actif (Apache `mod_rewrite`)
- 403 CSRF sur POST (FormData): passez par le client `ecoApi` qui injecte le `csrf_token` automatiquement
- Connexion MySQL: adaptez host/user/password dans `backend/config/config.php`
- MongoDB non installé: les avis passeront par le fallback SQL (`avis_fallback`)

## 13) Lien du tableau Kanban : 

https://trello.com/invite/b/68a9de30f74a1f0afbd8c3d0/ATTIa55b19cb101a14461a811192b4e4e432F4967AF9/ecoride3000

---
(c) Projet pédagogique – ECF EcoRide
