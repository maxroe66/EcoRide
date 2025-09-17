/*
Module : auth.js
Rôle : logique métier pour l’authentification (déconnexion utilisateur).
Importé par les vues nécessitant la gestion de session.
*/

import { post } from '../api/ecoApi.js';
import { showLoader, hideLoader, displayError } from '../utils/utils.js';

export async function logout() {
    if (!confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) return;

    showLoader(document.body);
    try {
        await post('/auth/logout', {});
    } catch (error) {
        // On logge en console pour debug sans interrompre le flux utilisateur.
        console.warn('Logout API error:', error);
        displayError('Erreur lors de la déconnexion.');
    } finally {
        hideLoader(document.body);
        // On nettoie l'état local même si l’appel réseau échoue (session probablement invalide côté serveur ou expirée).
        localStorage.removeItem('userLoggedIn');
        window.location.href = 'connexion.html';
    }
}
