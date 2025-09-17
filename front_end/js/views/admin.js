/*
Vue : admin.js
Rôle : Orchestration de la page d’administration EcoRide.
Ce fichier “view” initialise la page, gère les événements et délègue la logique métier au module adminModule.js.
*/


import {
  loadCsrfAdmin,
  adaptTablesResponsively,
  afficherCovoituragesAdmin,
  afficherStatsUtilisateurs,
  afficherUtilisateursAdmin,
  setupFormCreerEmploye,
  afficherGraphiqueCovoiturages,
  afficherGraphiqueCredits
} from '../modules/adminModule.js';

// Orchestration appelée par main.js pour la page admin
export async function initAdmin() {
  await loadCsrfAdmin();
  window.addEventListener('resize', adaptTablesResponsively);
  setTimeout(adaptTablesResponsively, 400);
  setTimeout(adaptTablesResponsively, 1100);
  afficherCovoituragesAdmin();
  afficherStatsUtilisateurs();
  afficherUtilisateursAdmin();
  setupFormCreerEmploye();
  afficherGraphiqueCovoiturages();
  afficherGraphiqueCredits();
}
