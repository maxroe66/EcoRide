/*
Vue : espace-utilisateur.js
Rôle : Orchestration de la page espace utilisateur.
Ce fichier “view” initialise la page, gère les événements et délègue la logique métier aux modules spécialisés.
Aligné avec main.js : exporte une fonction d'init sans gérer DOMContentLoaded ni le header.
*/

import { verifierStatutChauffeur, handleAjoutVehicule } from '../modules/espaceUtilisateurModule.js';
import { ensureCsrf } from '../api/ecoApi.js';

export async function initEspaceUtilisateur() {
    // S'assure que le token CSRF est présent pour les actions côté utilisateur
    await ensureCsrf();

    // Vérifie si l'utilisateur peut devenir chauffeur et ajuste l'UI (préférences visibles...)
    await verifierStatutChauffeur();

    // Gestion du formulaire d'ajout de véhicule
    const formVehicule = document.getElementById('formAjoutVehicule');
    if (formVehicule) {
        formVehicule.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleAjoutVehicule(formVehicule);
        });
    }
}
