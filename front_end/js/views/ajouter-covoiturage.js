/*
Vue : ajouter-covoiturage.js
Rôle : Orchestration de la page d’ajout de covoiturage.
*/

// Imports
// - handleAjoutCovoiturage: logique dédiée à la soumission du formulaire (appel API, gestion erreurs/succès)
// - chargerVehicules: remplit la liste déroulante des véhicules de l'utilisateur
// - ensureCsrf: garantit la présence d'un token CSRF avant toute action sensible
// Ces imports permettent de garder la "vue" légère: elle orchestre et délègue la logique métier aux modules.
import { handleAjoutCovoiturage } from '../modules/ajoutCovoiturage.js';
import { chargerVehicules } from '../modules/vehiculeModule.js';
import { ensureCsrf } from '../api/ecoApi.js';
/**
 * initAjouterCovoiturage
 * Orchestration de la page d'ajout de covoiturage sans gérer DOMContentLoaded ni le header (gérés globalement par main.js).
 * Étapes:
 *  1) ensureCsrf(): s'assure qu'un token CSRF est disponible pour les requêtes mutantes.
 *  2) chargerVehicules('selectVehicule'): alimente le <select> des véhicules de l'utilisateur.
 *  3) Ajoute l'écouteur submit sur le formulaire et délègue la soumission à handleAjoutCovoiturage(form).
 */
export async function initAjouterCovoiturage() {
    await ensureCsrf();
    chargerVehicules('selectVehicule');
    const formCovoit = document.getElementById('formAjoutCovoiturage');
    if (formCovoit) {
        formCovoit.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleAjoutCovoiturage(formCovoit);
        });
    }
}
