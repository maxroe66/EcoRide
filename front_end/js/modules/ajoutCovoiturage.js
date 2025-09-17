// Affichage de message spécifique à la page d'ajout (zone dédiée)
function displayError(msg, type) {
    const el = document.getElementById('ajoutCovoiturageMessage');
    if (el) {
        el.textContent = msg;
        el.style.color = (type === 'success') ? 'green' : 'red';
    }
}

/*
Module : ajoutCovoiturage.js
Rôle : logique métier pour la soumission du formulaire d’ajout de covoiturage.
Importé par la vue ajouter-covoiturage.js.
*/

import { ecoApi } from '../api/ecoApi.js';
import { showLoader, hideLoader } from '../utils/utils.js';

export async function handleAjoutCovoiturage(form) {
    const data = {
        lieu_depart: form.lieu_depart.value,
        lieu_arrivee: form.lieu_arrivee.value,
        date_depart: form.date_depart.value,
        heure_depart: form.heure_depart.value,
        heure_arrivee: form.heure_arrivee.value,
        prix: form.prix.value,
        nb_places: form.nb_places.value,
        vehicule_id: form.vehicule_id.value
    };
    const submitBtn = form.querySelector('button[type="submit"]');
    showLoader(submitBtn, 'Chargement...');
    try {
        const result = await ecoApi.post('/covoiturages', data);
        if (result.success) {
            displayError('✅ ' + (result.message || 'Covoiturage ajouté.'), 'success');
            // Rafraîchir le solde utilisateur avant la redirection
            try {
                const { checkUserSessionAndShowBanner } = await import('../modules/headerModule.js');
                await checkUserSessionAndShowBanner();
            } catch (_) { /* silencieux */ }
            setTimeout(() => { window.location.href = 'profil.html'; }, 1200);
        } else {
            displayError(result.message || 'Erreur lors de l\'ajout');
        }
    } catch (err) {
        const apiMsg = (err && (err.payload?.message || err.payload?.error)) || err?.message;
        displayError(apiMsg || 'Erreur lors de l\'ajout');
    } finally {
        hideLoader(submitBtn);
    }
}
