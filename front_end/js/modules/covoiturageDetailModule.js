/*
Module : covoiturageDetailModule.js
Rôle : logique métier pour la gestion des détails du covoiturage, avis, participation, helpers.
Importé par la vue covoiturage-detail.js.
*/

import { ecoApi } from '../api/ecoApi.js';
import { normalizeRide } from '../utils/normalizeRide.js'; // formatTime importé ailleurs si nécessaire

export async function getRideDetails(rideId) {
    if (!rideId) {
        return { error: 'Identifiant de covoiturage manquant' };
    }
    try {
        const resp = await ecoApi.get(`/covoiturages/details?id=${encodeURIComponent(rideId)}`);
        if (!resp.success) return { error: resp.message || resp.error || 'Covoiturage introuvable' };
    // Schéma normalisé: réponse = { success:true, schema_version:1, covoiturage:{...} }
    const ride = resp.covoiturage || null; // LEGACY: resp.data supprimé côté backend
        if (!ride) return { error: 'Données covoiturage indisponibles' };
        return { ride: normalizeRide(ride) };
    } catch (e) {
        return { error: 'Erreur de chargement des détails' };
    }
}

// normalizeRide & formatTime désormais importés depuis utils/normalizeRide.js

export function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric' 
    });
}

export async function getAvisValides() {
    try {
        const resp = await ecoApi.get('/avis/valides');
        if (resp.success && Array.isArray(resp.avis)) return resp.avis;
        return [];
    } catch (e) {
        return [];
    }
}

export async function getChauffeurReviews(rideId) {
    try {
        const resp = await ecoApi.get(`/avis/chauffeur?covoiturage_id=${encodeURIComponent(rideId)}&limit=20`);
        if (resp.success) return resp.reviews || [];
        return [];
    } catch (e) {
        return [];
    }
}

export async function checkUserSession() {
    try {
        const result = await ecoApi.get('/session');
        return result.connected;
    } catch (error) {
        return false;
    }
}

export async function participerCovoiturage(rideId) {
    if (!rideId) {
        return { success: false, message: 'Identifiant covoiturage requis.' };
    }
    try {
        const formData = new FormData();
        formData.append('covoiturage_id', rideId);
        formData.append('nb_places', 1);
        const result = await ecoApi.post('/covoiturages/participer', formData);
        if (result && result.success) {
            return { success: true, message: result.message || '🎉 Participation enregistrée ! Le débit se fera après validation du trajet.' };
        } else {
            return { success: false, message: (result && (result.message || result.error)) || 'Action impossible' };
        }
    } catch (error) {
        // Propager le message backend quand disponible
        const msg = (error && (error.payload?.message || error.payload?.error))
            || error?.message
            || 'Erreur lors de la participation. Veuillez réessayer.';
        return { success: false, message: msg };
    }
}
