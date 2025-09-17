/*
Vue : connexion.js
Rôle : Orchestration de la page connexion/inscription.
Ce fichier “view” initialise la page, gère les événements et délègue la logique métier aux modules spécialisés.
*/

// Imports: toute la logique UI/validation est déléguée au module connexionModule
// - ensureCsrf: prépare un token CSRF pour les opérations mutantes (login/register)
// - setupTabs: gère l’onglet Connexion / Inscription
// - setupPasswordValidation: feedback en direct sur la force / règles du mot de passe
// - setupLoginForm: binding des événements & soumission du formulaire de connexion
// - setupRegisterForm: binding des événements & soumission du formulaire d’inscription
import {
  ensureCsrf,
  setupTabs,
  setupPasswordValidation,
  setupLoginForm,
  setupRegisterForm
} from '../modules/connexionModule.js';

/**
 * initConnexion
 * Orchestration de la page Connexion/Inscription sans DOMContentLoaded ni header (gérés globalement par main.js).
 * Étapes:
 *  1) ensureCsrf(): sécurité CSRF pour les requêtes POST.
 *  2) setupTabs(): active l’UI à onglets (connexion/inscription).
 *  3) setupPasswordValidation(): branche la validation UX des mots de passe.
 *  4) setupLoginForm() & setupRegisterForm(): branchements des formulaires (soumission, loaders, erreurs) via le module.
 */
export async function initConnexion() {
  await ensureCsrf();
  setupTabs();
  setupPasswordValidation();
  setupLoginForm();
  setupRegisterForm();
  console.log('Page connexion initialisée (vue modulaire)');
}
