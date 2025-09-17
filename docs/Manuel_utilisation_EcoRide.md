# Manuel d’utilisation — EcoRide

Version: 1.0 — Date: 2025-09-17

## 1. Présentation de l’application
EcoRide est une application web de covoiturage focalisée sur l’écologie. Elle permet de rechercher, créer et participer à des trajets, avec une mise en avant des véhicules électriques. L’application couvre les principaux parcours: visiteur, utilisateur (passager/chauffeur), employé (modération), administrateur (statistiques et gestion des comptes).

Objectifs clés:
- Mettre en relation des chauffeurs et des passagers
- Faciliter la recherche de trajets par ville/date et appliquer des filtres (écologique, prix, durée, note)
- Gérer la participation avec un système de crédits et une commission fixe
- Encadrer les incidents et la modération des avis

## 2. Prérequis
- Windows + XAMPP (Apache + MySQL) installés
- Dossier du projet placé dans `C:\xampp\htdocs\EcoRide`
- Navigateur web récent (Chrome/Edge/Firefox)
- (Optionnel) MongoDB local pour les avis (fallback SQL disponible si absent)

## 3. Installation locale (rapide)
1. Démarrer Apache et MySQL dans XAMPP.
2. Créer une base MySQL `ecoride` puis importer `backend/database.sql` via phpMyAdmin.
3. (Option recommandé) Importer des données de démonstration:
   - Méthode automatique (génère les hash):
     - Dans PowerShell à la racine du projet: `php backend\\scripts\\generate_seed.php > backend\\seed_demo_ready.sql`
     - Importer `backend/seed_demo_ready.sql` dans phpMyAdmin
   - Ou bien éditer et importer `backend/seed_demo.sql` après avoir remplacé `<HASH_...>` par des hash `password_hash`.
4. Vérifier l’API:
   - http://localhost/EcoRide/api/ping
   - http://localhost/EcoRide/api/csrf-token
5. Ouvrir l’interface:
   - http://localhost/EcoRide/front_end/html/accueil.html

## 4. Comptes de démonstration
S’ils ont été importés via le seed prêt (seed_demo_ready.sql):
- Administrateur: `admin@ecoride.test` / `Admin@123!`
- Employé: `employe@ecoride.test` / `Employe@123!`
- Chauffeur: `driver@ecoride.test` / `Driver@123!`
- Passager: `passenger@ecoride.test` / `Passenger@123!`

Vous pouvez également créer un compte utilisateur via la page de connexion/inscription (20 crédits offerts).

## 5. Parcours utilisateur

### 5.1 Visiteur (US1–US5)
- Accueil: `accueil.html` — Présentation et recherche rapide
- Menu: navigation vers Covoiturages, Connexion, Contact
- Recherche de trajets: `covoiturage.html`
  - Renseigner la ville de départ/arrivée et la date
  - Appliquer des filtres: écologique, prix max, durée max, note minimale
- Détail d’un trajet: `covoiturage-detail.html`
  - Voir conducteur (pseudo, note), véhicule (marque, modèle, énergie), préférences
  - Voir et lire les avis

### 5.2 Utilisateur (US6–US11)
- Inscription / Connexion: `connexion.html`
  - Saisir pseudo, email, mot de passe (sécurisé)
  - 20 crédits offerts à la création
- Espace utilisateur: `espace-utilisateur.html`
  - Choisir ses rôles: passager, chauffeur (ou les deux)
  - Gérer ses préférences (fumeur/animaux)
- Véhicules: `espace-utilisateur.html` (section véhicule) / `ajouter-covoiturage.html`
  - Ajouter un véhicule: immatriculation, date 1ère immat., marque/modèle, énergie, couleurs, places
  - Un trajet est écologique si le véhicule est électrique
- Créer un covoiturage: `ajouter-covoiturage.html`
  - Définir départ/arrivée, date+heures, places, prix par personne (commission fixe prélevée par la plateforme)
- Participer à un covoiturage: bouton “Participer” sur `covoiturage-detail.html`
  - Double confirmation
  - Débit des crédits (escrow) et réservation des places
- Démarrer / Terminer un trajet (chauffeur): `profil.html`
  - Démarrer au départ, terminer à l’arrivée
  - Les passagers reçoivent une notification (mode développement: `backend/logs/mail_test.log`)
  - Validation par les passagers; libération des crédits chauffeur (commission déduite)
- Historique / Annulation: `profil.html`
  - Annuler une participation ou un trajet (mise à jour crédits/places, e-mail de notification)

### 5.3 Employé (US12)
- Page: `employe.html`
- Modération des avis: valider/refuser avant publication
- Suivi des incidents: voir trajets problématiques (pseudo, e-mail, date, lieux, descriptif)
- Résolution: décider d’un refund (passager) ou d’une release (chauffeur)

### 5.4 Administrateur (US13)
- Page: `admin.html`
- Gestion des comptes: création employé, suspension utilisateur
- Statistiques: nombre de covoiturages / jour, crédits gagnés / jour, total crédits gagnés

## 6. Astuces de navigation
- Menu principal disponible sur toutes les pages pour revenir à l’accueil, accéder aux covoiturages, se connecter ou contacter.
- Les boutons “Détail” permettent d’ouvrir la fiche complète d’un trajet.
- Les pages employé/admin exigent d’être connecté avec le rôle adapté.

## 7. URLs utiles (API)
- GET `/api/ping` — Diagnostic routeur
- GET `/api/csrf-token` — Jeton CSRF (les POST exigent son envoi)
- GET `/api/covoiturages?ville=Paris&date=2025-09-20` — Recherche
- POST `/api/auth/register` — Inscription
- POST `/api/auth/login` — Connexion
- POST `/api/covoiturages/participer` — Participer (connecté + CSRF)

## 8. Dépannage rapide
- 404 ou aucune réponse côté API:
  - Vérifier que l’URL commence par `http://localhost/EcoRide/api/...`
  - Vérifier que Apache mod_rewrite est actif et que `.htaccess` est pris en compte
- 403 CSRF lors d’un POST:
  - Le client front (`front_end/js/api/ecoApi.js`) récupère et injecte automatiquement le `csrf_token` (FormData ou JSON)
  - Rafraîchir la page et réessayer
- Connexion MySQL:
  - Adapter `backend/config/config.php` (host, user, password) à votre environnement XAMPP
- MongoDB non installé:
  - Les avis utilisent un fallback SQL (`avis_fallback`)

## 9. Contact
- Démonstration: `contact@ecoride.fr` (fictif)
- Dépôt: https://github.com/maxroe66/EcoRide

---
Ce document peut être exporté en PDF et fourni au jury pour faciliter l’évaluation des parcours.
