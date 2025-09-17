/*
Module : covoiturageModule.js
Rôle : logique métier pour la recherche, affichage, filtres et mock des covoiturages.
Importé par la vue covoiturage.js.
*/

import { get } from '../api/ecoApi.js';

/**
 * Recherche côté serveur avec pagination & filtres.
 * @param {object} opts
 *  - departure, arrival, date (YYYY-MM-DD ou DD/MM/YYYY)
 *  - eco (bool)
 *  - maxPrice (number)
 *  - page (number)
 *  - perPage (number)
 */
export async function rechercherCovoiturages({ departure, arrival, date, eco, maxPrice, minRating, maxDuration, page = 1, perPage = 10 }) {
    const params = new URLSearchParams();
    if (departure) params.append('lieu_depart', departure.trim().replace(/\s+/g, ' '));
    if (arrival) params.append('lieu_arrivee', arrival.trim().replace(/\s+/g, ' '));
    if (date) params.append('date_depart', formatDate(date));
    params.append('nb_places', '1');
    if (eco) params.append('ecologique', 'true');
    if (maxPrice) params.append('prix_max', String(maxPrice));
    if (minRating) params.append('min_rating', String(minRating));
    // maxDuration est déjà fourni en minutes (assemblé depuis selects heures + minutes dans la vue)
    if (typeof maxDuration === 'number' && maxDuration > 0) {
        params.append('max_duration', String(maxDuration));
    }
    params.append('page', String(page));
    params.append('per_page', String(perPage));
    const finalUrl = `/covoiturages?${params.toString()}`;
    try {
        const data = await get(finalUrl);
        if (data && data.success) {
            const payload = Array.isArray(data.results) ? data.results : (Array.isArray(data.data) ? data.data : []);
            return {
                results: payload,
                suggestion: data.suggestion,
                pagination: data.pagination || null,
                schema_version: data.schema_version || 1
            };
        } else {
            return { results: [], suggestion: data && data.suggestion, error: data && (data.error || data.message) };
        }
    } catch (error) {
        return { results: [], error: error.message };
    }
}

// Normalisation des filtres côté module (retire la logique du front)
export function parseSearchFilters({
    departure = '',
    arrival = '',
    date = '',
    eco = false,
    maxPriceStr = '',
    minRatingStr = '',
    maxDurationHoursStr = '',
    maxDurationMinutesStr = '',
    page = 1,
    perPage = 10
} = {}) {
    const norm = (s) => (s || '').trim().replace(/\s+/g, ' ');
    const departureN = norm(departure);
    const arrivalN = norm(arrival);
    const dateN = formatDate((date || '').trim());
    const ecoB = !!eco;
    let maxPrice = parseInt(maxPriceStr, 10);
    maxPrice = Number.isFinite(maxPrice) && maxPrice > 0 ? maxPrice : null;
    let minRating = parseFloat(minRatingStr);
    minRating = Number.isFinite(minRating) && minRating >= 0 ? Math.min(minRating, 5) : null;
    let h = parseInt(maxDurationHoursStr, 10);
    let m = parseInt(maxDurationMinutesStr, 10);
    h = Number.isFinite(h) && h > 0 ? h : 0;
    m = Number.isFinite(m) && m > 0 ? m : 0;
    const maxDuration = (h * 60 + m) > 0 ? (h * 60 + m) : null;
    const pageN = Number.isFinite(parseInt(page, 10)) ? parseInt(page, 10) : 1;
    const perPageN = Number.isFinite(parseInt(perPage, 10)) ? parseInt(perPage, 10) : 10;
    return { departure: departureN, arrival: arrivalN, date: dateN, eco: ecoB, maxPrice, minRating, maxDuration, page: pageN, perPage: perPageN };
}

export function formatDate(dateValue) {
    if (!dateValue) return '';
    if (dateValue.match(/^\d{2}[-/]\d{2}[-/]\d{4}$/)) {
        const parts = dateValue.split(/[-/]/);
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    } else if (dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateValue;
    }
    return dateValue;
}

export function generateMockResults() {
    return [
        { id: 1, driver: { pseudo: 'Marie_Eco', rating: 4.8, initials: 'ME', isEcological: true }, departureTime: '08h00', arrivalTime: '10h30', duration: '2h30', price: 15, availableSeats: 3, car: 'Tesla Model 3' },
        { id: 2, driver: { pseudo: 'PierreV', rating: 4.2, initials: 'PV', isEcological: false }, departureTime: '10h00', arrivalTime: '12h30', duration: '2h30', price: 20, availableSeats: 2, car: 'Renault Clio' },
        { id: 3, driver: { pseudo: 'Sophie_Green', rating: 4.9, initials: 'SG', isEcological: true }, departureTime: '12h00', arrivalTime: '14h30', duration: '2h30', price: 25, availableSeats: 1, car: 'Nissan Leaf' },
        { id: 4, driver: { pseudo: 'LucasK', rating: 4.5, initials: 'LK', isEcological: false }, departureTime: '14h00', arrivalTime: '16h30', duration: '2h30', price: 18, availableSeats: 2, car: 'Peugeot 308' },
        { id: 5, driver: { pseudo: 'Emma_Verte', rating: 4.7, initials: 'EV', isEcological: true }, departureTime: '16h00', arrivalTime: '18h30', duration: '2h30', price: 22, availableSeats: 3, car: 'BMW i3' },
        { id: 6, driver: { pseudo: 'Thomas', rating: 4.1, initials: 'TH', isEcological: false }, departureTime: '18h00', arrivalTime: '20h30', duration: '2h30', price: 30, availableSeats: 1, car: 'Volkswagen Golf' }
    ];
}

// Tous les filtres sont désormais appliqués côté serveur (minRating, maxDuration inclus).
// Aucun filtrage client résiduel nécessaire ici.
