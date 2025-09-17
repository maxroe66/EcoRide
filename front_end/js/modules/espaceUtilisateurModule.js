
/*
Module : espaceUtilisateurModule.js
Rôle : logique métier pour l’espace utilisateur (ajout véhicule, gestion préférences chauffeur).
Importé par la vue espace-utilisateur.js.
*/

import { ecoApi } from '../api/ecoApi.js';
import { showLoader, hideLoader, displayError } from '../utils/utils.js';

export async function verifierStatutChauffeur() {
    const btn = document.getElementById('btnText');
    try {
        if (btn) showLoader(btn);
        const userData = await ecoApi.get('/utilisateur/profil', { credentials: 'include' });
        if (btn) hideLoader(btn);
        if (!(userData && userData.success && userData.user)) {
            displayError('Impossible de récupérer le profil utilisateur.');
            return;
        }
        const preferencesSection = document.getElementById('preferencesSection');
        const titre = document.querySelector('h3');
        const f = document.getElementById('fumeur');
        const a = document.getElementById('animaux');
        if (!preferencesSection || !btn || !titre) return;
        if (!userData.user.is_chauffeur) {
            preferencesSection.style.display = 'block';
            btn.textContent = 'Devenir chauffeur';
            titre.textContent = 'Devenir chauffeur';
            if (f) { f.required = true; f.disabled = false; }
            if (a) { a.required = true; a.disabled = false; }
        } else {
            preferencesSection.style.display = 'none';
            btn.textContent = 'Ajouter le véhicule';
            titre.textContent = 'Ajouter un véhicule';
            if (f) { f.required = false; f.disabled = true; }
            if (a) { a.required = false; a.disabled = true; }
        }
    } catch (error) {
        if (btn) hideLoader(btn);
        displayError('Erreur lors de la vérification du statut.');
        console.error('Erreur lors de la vérification du statut:', error);
    }
}

export async function handleAjoutVehicule(form) {
    if (!form) return;
    const submitBtn = form.querySelector('[type="submit"]');
    const msg = document.getElementById('ajoutVehiculeMessage');
    const val = (el) => (el && 'value' in el) ? el.value : '';
    const data = {
        marque: val(form.marque),
        modele: val(form.modele),
        couleur: val(form.couleur),
        plaque: val(form.plaque),
        date_premiere_immatriculation: val(form.date_premiere_immatriculation),
        energie: val(form.energie)
    };
    const preferencesSection = document.getElementById('preferencesSection');
    if (preferencesSection && preferencesSection.style.display !== 'none') {
        data.preferences = {
            fumeur: val(form.fumeur),
            animaux: val(form.animaux),
            autres_preferences: val(form.autresPreferences)
        };
    }
    try {
        if (submitBtn) { submitBtn.disabled = true; showLoader(submitBtn); }
        const result = await ecoApi.post('/vehicules', data);
        if (result.success) {
            if (msg) { msg.style.color = 'green'; msg.textContent = '✅ ' + (result.message || 'Véhicule ajouté'); }
            setTimeout(() => { window.location.href = 'profil.html'; }, 800);
        } else {
            if (msg) { msg.style.color = 'red'; msg.textContent = '❌ ' + (result.message || 'Erreur lors de l\'ajout'); }
        }
    } catch (e) {
        if (msg) { msg.style.color = 'red'; msg.textContent = '❌ Erreur réseau.'; }
    } finally {
        if (submitBtn) { hideLoader(submitBtn); submitBtn.disabled = false; }
    }
}
