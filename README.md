EcoRide3000

Plateforme de covoiturage écologique développée dans le cadre du TP DWWM.

## 🎯 Objectif
Créer une application web qui favorise les déplacements en voiture électrique.

## 🧩 Fonctionnalités (User Stories)
- Page d’accueil avec recherche d’itinéraire
- Menu de navigation
- Recherche et filtres de covoiturage
- Vue détaillée d’un trajet
- Création de compte (20 crédits offerts)
- Réservation de covoiturage
- Espace utilisateur (chauffeur/passager)
- Historique des trajets
- Espace employé et administrateur

## 🛠️ Stack technique
- **Front-end** : HTML5, CSS3 (Bootstrap), JavaScript
- **Back-end** : PHP (PDO)
- **BDD relationnelle** : MySQL
- **BDD NoSQL** : MongoDB (avis de trajets)
- **Déploiement** : Vercel / Heroku / Fly.io

## 🚀 Installation locale
```bash
# Cloner le dépôt
git clone https://github.com/ton-utilisateur/EcoRide3000.git

# Se placer dans le dossier
cd EcoRide3000

# Importer la base de données
mysql -u root -p < database/schema.sql

# Lancer le serveur local (ex: XAMPP, WAMP)
```

## 🔐 Identifiants de test
- Utilisateur : `test@ecoride.fr` / `motdepasse123`
- Employé : `employe@ecoride.fr` / `motdepasse123`
- Admin : `admin@ecoride.fr` / `motdepasse123`

## 📎 Liens utiles
- [Trello - Gestion de projet](https://trello.com/invite/b/68a9de30f74a1f0afbd8c3d0/ATTIa55b19cb101a14461a811192b4e4e432F4967AF9/ecoride3000)
- [Maquettes Figma]
(Maquettes Figma (PDF))
- [Charte graphique PDF](lien_vers_charte_graphique)
- [Documentation technique](lien_vers_doc_technique)
- [Manuel d’utilisation PDF](lien_vers_manuel_utilisation)

## 📂 Structure Git recommandée
- Branche principale : `main`
- Branche de développement : `develop`
- Branches fonctionnelles : `feature/nom_fonctionnalite`

Chaque fonctionnalité est développée sur une branche dédiée, testée, puis fusionnée dans `develop`. Une fois stable, `develop` est fusionnée dans `main`.

## 📄 Livrables attendus
- Code source sur GitHub (public)
- Application déployée
- Maquettes (3 desktop + 3 mobile)
- Charte graphique
- Documentation technique
- Manuel d’utilisation
- Kanban Trello


    Liens
  Trello : Tableau kanban (https://trello.com/invite/b/68a9de30f74a1f0afbd8c3d0/ATTIa55b19cb101a14461a811192b4e4e432F4967AF9/ecoride3000)
