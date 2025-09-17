/*
Vue : accueil.js
Rôle : Orchestration de la page d’accueil.
Ce fichier “view” initialise la page, gère les événements et délègue toute la logique métier aux modules spécialisés.
La séparation “view”/“modules”/“utils” garantit la clarté et la maintenabilité du code
*/


import { validateSearchFields, redirectToResults } from '../modules/rechercheCovoiturage.js';
import { addDateField } from '../utils/formUtils.js';
import { showLoader, hideLoader, displayError } from '../utils/utils.js';

// Initialisation orchestrée par main.js via data-page="accueil"
export function initAccueil() {
    // 1) Récupération des éléments du DOM nécessaires
    const departureInput = document.getElementById('departure');
    const arrivalInput = document.getElementById('arrival');
    const searchBtn = document.querySelector('.search-btn');

    // 2) Injection du champ date via un utilitaire (respect du design global)
    addDateField('.form-row');

    // 3) Handler principal: valide et lance la recherche
    //    Contrat rapide:
    //    - Entrées: valeurs des champs (départ, arrivée, date)
    //    - Effets: affiche une erreur UI si invalide, sinon déclenche la navigation vers la page de résultats
    //    - Accessibilité: showLoader gère aria-busy/disabled/label (via data-loading-label dans le HTML)
    function handleSearch() {
        // 3.1) Lecture et nettoyage des valeurs
        const departure = departureInput.value.trim();
        const arrival = arrivalInput.value.trim();
        const date = document.getElementById('travel-date').value;
        // 3.2) Validation métier côté front (rapide); la vérité reste côté backend
        const errorMsg = validateSearchFields(departure, arrival, date);
        if (errorMsg) {
            // 3.3) Affichage d'un message d'erreur dans la zone prévue (ou alert de secours)
            displayError(errorMsg);
            return;
        }
        // 3.4) Feedback utilisateur: activation du loader sur le bouton
        //      Le label de chargement est lu depuis data-loading-label sur le bouton (séparation présentation/JS)
    showLoader(searchBtn);
        // Redirection immédiate (pas de faux délai)
        // 3.5) On libère le bouton avant la navigation (pratique si la navigation est différée/interceptée)
        hideLoader(searchBtn);
        // 3.6) Délégation: la navigation est gérée par le module (URL, paramètres, etc.)
        redirectToResults(departure, arrival, date);
    }

    // 4) Câblage des événements UI
    if (searchBtn) {
        searchBtn.addEventListener('click', handleSearch);
    }
    [departureInput, arrivalInput].forEach(function (input) {
        if (!input) return;
        input.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                handleSearch();
            }
        });
    });
    
    console.log("Page d'accueil initialisée");
}

// Initialisation du header désormais centralisée par main.js
