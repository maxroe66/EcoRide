# EcoRide — Dossier de Soutenance

## Sécurité Front-End

Cette section présente ce que nous avons mis en place côté front pour la sécurité, comment cela fonctionne concrètement dans le code, et les améliorations prévues. Elle est conçue pour évoluer au fil du projet.

### Objectifs
- Réduire les risques de XSS (injection de code côté client)
- Prévenir les actions non autorisées (CSRF) en collaboration avec le back
- Standardiser les erreurs et messages pour éviter les injections via messages serveurs
- Limiter l’exposition de données sensibles côté navigateur

### Mécanismes mis en place
- API client centralisée avec CSRF
  - Fichier: `front_end/js/api/ecoApi.js`
  - Points clés:
    - Centralisation des appels `fetch` (GET/POST/PUT/DELETE)
    - Récupération du token CSRF via `/csrf-token` et réinjection automatique sur les requêtes mutantes
    - Retry ciblé en cas d’erreur 403 liée au CSRF
    - Gestion propre de `FormData` vs JSON (en-têtes adaptés)

- Rendu DOM “safe by default”
  - Vues et modules créent des nœuds DOM et utilisent `textContent` pour les données utilisateurs (ex: `front_end/js/views/covoiturage.js`)
  - Réduction drastique de l’usage de `innerHTML` lorsque la donnée vient de l’API

- Échappement des données utilisées en HTML
  - Exemple: `escapeHtml` dans `front_end/js/modules/profilModule.js` pour sécuriser les interpolations dans des templates HTML
  - Principe: tout contenu non-fiable doit être échappé avant injection via `innerHTML`

- Construction sûre des URLs
  - Utilisation systématique de `URLSearchParams` (ex: `rechercheCovoiturage.js`) pour éviter les concaténations risquées

- Standardisation de l’affichage des erreurs et messages
  - `front_end/js/utils/utils.js` fournit `displayError`, `displayBanner`, `extractApiMessage`
  - Messages rendus via `textContent` (pas d’HTML interprété)

- Navigation maîtrisée
  - Redirections internes explicites (ex: `window.location.href = 'connexion.html'`), pas d’open redirect depuis des entrées utilisateur

- Accessibilité qui renforce l’UX sécurisée
  - Menus et modales avec `aria-*`, gestion du focus, fermeture via Escape (réduit erreurs d’usage)

### Bonnes pratiques de rendu (anti‑XSS)
- Par défaut, créer des éléments (`document.createElement`) et remplir via `textContent`
- N’utiliser `innerHTML` que pour du markup statique contrôlé; si des données dynamiques sont nécessaires, les échapper (`escapeHtml`) ou basculer sur `textContent`
- Ne jamais injecter directement du contenu issu du serveur dans `innerHTML` sans échappement préalable

### Gestion des messages/erreurs
- `extractApiMessage` extrait un texte propre depuis la réponse ou l’erreur
- `displayError`/`displayBanner` utilisent `textContent` et attribuent des rôles ARIA pour un rendu sûr et accessible

### Stockage local / session
- Usage limité à des indicateurs UX (ex: flash messages, drapeaux simples)
- Aucun secret/jeton d’authentification stocké côté front (réduit l’impact d’une XSS)

### Prouver ces points au jury (exemples concrets)
- CSRF: montrer `ensureCsrf()` et l’injection du token dans `ecoApi.js`
- Anti‑XSS DOM: montrer le rendu des cartes dans `front_end/js/views/covoiturage.js` (création d’éléments + `textContent`)
- Messages sûrs: montrer `displayBanner`/`displayError` dans `front_end/js/utils/utils.js`
- Échappement: montrer `escapeHtml` dans `profilModule.js` et son usage lors d’interpolations HTML

### Points de vigilance (connus)
- `innerHTML` subsiste dans certaines vues (ex: `front_end/js/views/covoiturage-detail.js`) pour des blocs composites. Tant que les valeurs sont échappées/contrôlées côté serveur, le risque est faible, mais l’anti‑XSS est plus robuste avec DOM + `textContent` ou un échappement systématique côté front.
- La validation client (ex: recherche) reste volontairement simple: la validation forte est côté back. Un durcissement client (normalisation d’entrées) est envisagé pour la cohérence.

### Améliorations planifiées
- Centraliser `escapeHtml` dans `front_end/js/utils/utils.js` et l’utiliser partout où `innerHTML` est nécessaire
- Réduire l’usage de `innerHTML` avec des données API dans les vues et préférer DOM + `textContent`
- Ajouter ESLint/Prettier avec règles de sécurité (ex: `eslint-plugin-security`)
- Ajouter des tests unitaires pour les helpers sensibles (validation de dates, `extractApiMessage`, échappement)
- Mettre en place un bundler (Vite/Rollup) pour un build prod (minification, splitting, cache-busting)

---

Cette section évoluera avec les prochaines itérations (audit, correctifs, et nouvelles protections côté front).




## Sécurité Back-End

Cette section synthétise l’architecture et les protections côté serveur, avec des exemples concrets à présenter et les prochains durcissements envisagés.

### Objectifs
- Authentification/autorisation fiables (sessions, rôles)
- Protection CSRF sur toutes les opérations mutantes
- Prévention des injections SQL (requêtes préparées)
- Réponses JSON uniformes et sobres (pas de fuite d’infos sensibles)
- Journalisation utile côté serveur

### Mécanismes en place
- Routage et garde d’accès
  - Fichiers: `backend/routes/Router.php`, `backend/routes/routes.php`, `backend/includes/router_guard.php`
  - Règles appliquées avant l’inclusion du contrôleur: `auth`, `role:…`, `roles:…`
  - Blocage des accès directs aux contrôleurs (constante `ECO_VIA_ROUTER`)

- Sessions et CSRF
  - Fichier: `backend/includes/bootstrap.php`
  - Démarrage session idempotent, génération de jeton CSRF (`random_bytes`) stocké en session
  - Vérification via `eco_verify_csrf()` pour les POST/PUT/DELETE

- Authentification et mots de passe
  - Fichiers: `controllers/auth/*.php`, `services/UtilisateurService.php`, `models/Utilisateur.php`
  - Hash sécurisé avec `password_hash`, vérification `password_verify`
  - `session_regenerate_id(true)` après login

- Accès base de données et SQL
  - Fichiers: `backend/models/*`, `backend/services/*`
  - Connexion PDO (exceptions activées), requêtes préparées systématiques (paramètres liés)
  - Exemple notable: `RechercheCovoiturage.php` gère les filtres/pagination via PDO préparé

- Réponses JSON uniformisées et log
  - Fichier: `backend/includes/bootstrap.php` (+ `response_helper.php`)
  - Helpers `eco_json_success`/`eco_json_error`, format v2 activable par accept/param
  - Logger simple fichier `EcoLogger::log(channel, message)` dans `backend/logs/`

### Exemples à montrer au jury
- CSRF côté serveur: montrer `eco_verify_csrf()` appelé dans un contrôleur POST (ex: `auth_login.php`, `participer_covoiturage.php`)
- Password hashing: `UtilisateurService::inscription()` et `connexion()` (hash + verify)
- PDO préparé: `RechercheCovoiturage::rechercher()` (filtres, LIKE/=`?`, LIMIT/OFFSET)
- Autorisation par rôle: route `roles:administrateur,employe` dans `routes.php`

### Points de vigilance (connus)
- CORS: routes publiques exposent `Access-Control-Allow-Origin: *` (OK en lecture publique; à restreindre en prod)
- Validation entrée: renforcer la normalisation/validation serveur (emails, dates, longueurs, enums) de façon systématique
- Gestion d’erreurs: éviter `die()` et harmoniser toutes les erreurs via `eco_json_error` (logging côté serveur, message client sobre)
- Sessions/cookies: en prod, activer `HttpOnly`, `Secure` (HTTPS), `SameSite` strict ou lax, timeouts
- Entêtes sécurité HTTP: CSP de base, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `X-Frame-Options`
- Rate limiting / anti brute-force: ajouter limitation sur `auth_login`
- Secrets/config: basculer entièrement sur `.env` (non exposé) pour DB/SMTP; éviter des valeurs en dur
- Exposition données: veiller à ne jamais renvoyer des champs sensibles (ex: hash password) dans les payloads

### Améliorations planifiées
- Centraliser une lib de validation (sanitisation/normalisation) côté serveur et l’appliquer dans chaque contrôleur
- Ajouter un middleware CORS configurable par environnement (origins autorisés)
- Introduire un rate limiter simple (par IP + user) sur les endpoints sensibles (login)
- Activer et documenter les en-têtes de sécurité HTTP au niveau front-controller (ou serveur web)
- Uniformiser toutes les réponses d’erreurs via les helpers JSON (pas de `die()`)
- Gestion des sessions: paramètres cookies sécurisés et rotation périodique d’ID
- Améliorer la rotation et permissions des logs

---

Cette section Back-End sera enrichie au fil des itérations (check-list déploiement, guide configuration `.env`, et matrices de rôles).

## Formulation utile devant le jury

“Pour l’ECF, j’ai priorisé la couverture des US et la sécurité. La vérification s’est faite par parcours manuel reproductible et requêtes API préparées. L’architecture est prête pour des tests automatisés (e2e/API), que je placerais en prochaine étape.”
