/*
Vue : covoiturage.js
Rôle : Orchestration de la page covoiturage.
- Lance la recherche et gère l'état de chargement + erreurs
- Relie les filtres (écologique, prix, durée, note) et la pagination
- Affiche un flash message global si présent (post-redirect)
*/


import { rechercherCovoiturages, parseSearchFilters } from '../modules/covoiturageModule.js';
import { displayError, showLoader, hideLoader, displayBanner } from '../utils/utils.js';

export async function initCovoiturage() {
    // Point d'entrée appelé par main.js via data-page. Pas de DOMContentLoaded ici.
    const departureInput = document.getElementById('departure');
    const arrivalInput = document.getElementById('arrival');
    const dateInput = document.getElementById('travel-date');
    const searchBtn = document.getElementById('searchBtn');
    const resultsContainer = document.getElementById('resultsContainer');
    const carImageSection = document.getElementById('carImageSection');
    const loadingMessage = document.getElementById('loadingMessage');
    const filtersSection = document.getElementById('filtersSection');
    const noResults = document.getElementById('noResultsMessage');

    let allResults = [];
    let filteredResults = [];
    let currentPage = 1;
    let lastPagination = null;

    // Flash success (après actions d'autres pages, ex: participation) – affiché dans #globalMessages
    try {
        const flash = sessionStorage.getItem('flash_success');
        if (flash) {
            displayBanner(flash, { type: 'success', container: '#globalMessages' });
            sessionStorage.removeItem('flash_success');
        }
    } catch {}

    const urlParams = new URLSearchParams(window.location.search);
    // Pré-remplissage (deep-link depuis la page d'accueil / liens externes)
    if (urlParams.get('from') && urlParams.get('to') && urlParams.get('date')) {
        departureInput.value = urlParams.get('from');
        arrivalInput.value = urlParams.get('to');
        dateInput.value = urlParams.get('date');
        await searchRides();
    }

    // Déclenche la recherche et attache les écouteurs de filtres
    searchBtn.addEventListener('click', searchRides);
    setupFilters();

    async function searchRides(page = 1) {
        // Fonction principale de recherche
        // - Construit les options via parseSearchFilters
        // - Appelle le module métier
        // - Met à jour l'UI (chargement, suggestions, résultats, pagination)
        showLoader(loadingMessage);
        currentPage = page;
        const ecoFilter = document.getElementById('ecoFilter');
        const maxPriceEl = document.getElementById('maxPrice');
        const maxDurationHoursEl = document.getElementById('maxDurationHours');
        const maxDurationMinutesEl = document.getElementById('maxDurationMinutes');
        const minRatingEl = document.getElementById('minRating');
        const opts = parseSearchFilters({
            departure: departureInput && departureInput.value,
            arrival: arrivalInput && arrivalInput.value,
            date: dateInput && dateInput.value,
            eco: ecoFilter && ecoFilter.checked,
            maxPriceStr: maxPriceEl && maxPriceEl.value,
            minRatingStr: minRatingEl && minRatingEl.value,
            maxDurationHoursStr: maxDurationHoursEl && maxDurationHoursEl.value,
            maxDurationMinutesStr: maxDurationMinutesEl && maxDurationMinutesEl.value,
            page: currentPage,
            perPage: 10
        });
        const { results, suggestion, error, pagination } = await rechercherCovoiturages(opts);
        hideLoader(loadingMessage);
        allResults = results;
        filteredResults = [...allResults];
        // Si aucun résultat brut (ex: prix max trop bas), on nettoie tout de suite avant filtrage local
        if (allResults.length === 0) {
            resultsContainer.innerHTML = '';
            if (noResults) noResults.style.display = 'block';
        }
        lastPagination = pagination;
        if (suggestion && (!allResults || allResults.length === 0)) {
            afficherSuggestion(suggestion);
        } else {
            masquerSuggestion();
        }
        if (error) displayError('Erreur lors de la recherche : ' + error);
        // Plus de filtrage client : résultats déjà filtrés côté serveur
        if (allResults.length > 0) {
            displayResults(allResults);
        }
        renderPagination();
    }

    function afficherSuggestion(s) {
        // Rend une petite zone d'aide quand l'API propose une autre date disponible
        let zone = document.getElementById('suggestionZone');
        if (!zone) {
            zone = document.createElement('div');
            zone.id = 'suggestionZone';
            zone.style.margin = '1rem 0';
            zone.style.padding = '0.8rem 1rem';
            zone.style.background = '#fff3cd';
            zone.style.border = '1px solid #ffeeba';
            zone.style.borderRadius = '6px';
            zone.style.color = '#856404';
            resultsContainer.parentNode.insertBefore(zone, resultsContainer);
        }
        zone.innerHTML = `Aucun trajet pour cette date. Prochaine date avec trajets : <strong>${s.date}</strong> (${s.count} disponibles). <button id="applySuggestionBtn" class="detail-btn" style="margin-left:12px;display:inline-flex;align-items:center;gap:6px;">📅 <span>Choisir cette date</span></button>`;
        const btn = document.getElementById('applySuggestionBtn');
        if (btn) {
            btn.onclick = () => {
                dateInput.value = s.date;
                searchRides();
            };
        }
        zone.style.display = 'block';
    }
    function masquerSuggestion() {
        // Cache la zone de suggestion si non pertinente
        const zone = document.getElementById('suggestionZone');
        if (zone) zone.style.display = 'none';
    }

    function setupFilters() {
        // Rattache les filtres et relance la recherche en page 1
        const ecoFilter = document.getElementById('ecoFilter');
        const maxPrice = document.getElementById('maxPrice');
    const maxDurationHours = document.getElementById('maxDurationHours');
    const maxDurationMinutes = document.getElementById('maxDurationMinutes');
        const minRating = document.getElementById('minRating');
        if (ecoFilter) ecoFilter.addEventListener('change', () => { currentPage = 1; searchRides(1); });
        // Debounce sur prix pour éviter rafales de requêtes
        if (maxPrice) {
            let priceTimer = null;
            maxPrice.addEventListener('input', () => {
                if (priceTimer) clearTimeout(priceTimer);
                priceTimer = setTimeout(() => { currentPage = 1; searchRides(1); }, 400);
            });
        }
    if (maxDurationHours) maxDurationHours.addEventListener('change', () => { currentPage = 1; searchRides(1); });
    if (maxDurationMinutes) maxDurationMinutes.addEventListener('change', () => { currentPage = 1; searchRides(1); });
        if (minRating) minRating.addEventListener('change', () => { currentPage = 1; searchRides(1); });
    }

    // applyFilters supprimé (logique déplacée serveur)

    function renderPagination() {
        // Crée/Met à jour la pagination selon les infos renvoyées par l'API
        let container = document.getElementById('paginationContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'paginationContainer';
            container.style.marginTop = '16px';
            resultsContainer.parentNode.appendChild(container);
        }
        container.innerHTML = '';
        if (!lastPagination || lastPagination.total_pages <= 1) return;
        const { page, total_pages } = lastPagination;
        const prevBtn = document.createElement('button');
        prevBtn.textContent = '← Précédent';
        prevBtn.disabled = page <= 1;
        prevBtn.onclick = () => searchRides(page - 1);
        const info = document.createElement('span');
        info.style.margin = '0 12px';
        info.textContent = `Page ${page}/${total_pages}`;
        const nextBtn = document.createElement('button');
        nextBtn.textContent = 'Suivant →';
        nextBtn.disabled = page >= total_pages;
        nextBtn.onclick = () => searchRides(page + 1);
        container.appendChild(prevBtn);
        container.appendChild(info);
        container.appendChild(nextBtn);
    }

    function displayResults(results) {
        // Rendu des cartes résultats – sécurisé côté front: création de nœuds + textContent (pas d'innerHTML pour les données)
        hideLoader(loadingMessage);
        if (filtersSection) filtersSection.style.display = 'block';
        if (noResults) noResults.style.display = 'none';
        if (results.length === 0) {
            if (noResults) noResults.style.display = 'block';
            return;
        }
        resultsContainer.innerHTML = '';

        const safeText = (v) => (v === null || v === undefined) ? '' : String(v);

        results.forEach(ride => {
            const card = document.createElement('div');
            card.className = 'ride-card';

            // Header
            const header = document.createElement('div');
            header.className = 'ride-header';

            const driverInfo = document.createElement('div');
            driverInfo.className = 'driver-info';

            const avatar = document.createElement('div');
            avatar.className = 'driver-avatar';
            avatar.textContent = safeText(ride?.driver?.initials);

            const driverDetails = document.createElement('div');
            driverDetails.className = 'driver-details';

            const nameEl = document.createElement('h3');
            nameEl.textContent = safeText(ride?.driver?.pseudo);

            const ratingEl = document.createElement('div');
            ratingEl.className = 'driver-rating';
            ratingEl.textContent = `🌱 ${safeText(ride?.driver?.rating)}/5`;

            driverDetails.appendChild(nameEl);
            driverDetails.appendChild(ratingEl);
            driverInfo.appendChild(avatar);
            driverInfo.appendChild(driverDetails);
            header.appendChild(driverInfo);

            if (ride?.driver?.isEcological) {
                const badge = document.createElement('span');
                badge.className = 'eco-badge';
                badge.textContent = '🌱 Écologique';
                header.appendChild(badge);
            }

            // Bloc horaires
            const details1 = document.createElement('div');
            details1.className = 'ride-details';

            const depItem = document.createElement('div');
            depItem.className = 'detail-item';
            const depLabel = document.createElement('div');
            depLabel.className = 'label';
            depLabel.textContent = 'Départ';
            const depValue = document.createElement('div');
            depValue.className = 'value';
            depValue.textContent = safeText(ride?.departureTime);
            depItem.appendChild(depLabel);
            depItem.appendChild(depValue);

            const arrItem = document.createElement('div');
            arrItem.className = 'detail-item';
            const arrLabel = document.createElement('div');
            arrLabel.className = 'label';
            arrLabel.textContent = 'Arrivée';
            const arrValue = document.createElement('div');
            arrValue.className = 'value';
            arrValue.textContent = safeText(ride?.arrivalTime);
            arrItem.appendChild(arrLabel);
            arrItem.appendChild(arrValue);

            const durItem = document.createElement('div');
            durItem.className = 'detail-item';
            const durLabel = document.createElement('div');
            durLabel.className = 'label';
            durLabel.textContent = 'Durée';
            const durValue = document.createElement('div');
            durValue.className = 'value';
            durValue.textContent = safeText(ride?.duration);
            durItem.appendChild(durLabel);
            durItem.appendChild(durValue);

            details1.appendChild(depItem);
            details1.appendChild(arrItem);
            details1.appendChild(durItem);

            // Bloc infos supplémentaires
            const details2 = document.createElement('div');
            details2.className = 'ride-details';

            const seatsItem = document.createElement('div');
            seatsItem.className = 'detail-item';
            const seatsLabel = document.createElement('div');
            seatsLabel.className = 'label';
            seatsLabel.textContent = 'Places restantes';
            const seatsValue = document.createElement('div');
            seatsValue.className = 'value';
            seatsValue.textContent = safeText(ride?.availableSeats);
            seatsItem.appendChild(seatsLabel);
            seatsItem.appendChild(seatsValue);

            const carItem = document.createElement('div');
            carItem.className = 'detail-item';
            const carLabel = document.createElement('div');
            carLabel.className = 'label';
            carLabel.textContent = 'Véhicule';
            const carValue = document.createElement('div');
            carValue.className = 'value';
            carValue.textContent = safeText(ride?.car);
            carItem.appendChild(carLabel);
            carItem.appendChild(carValue);

            const priceItem = document.createElement('div');
            priceItem.className = 'detail-item';
            const priceLabel = document.createElement('div');
            priceLabel.className = 'label';
            priceLabel.textContent = 'Prix';
            const priceValue = document.createElement('div');
            priceValue.className = 'value price';
            priceValue.textContent = `${safeText(ride?.price)}€`;
            priceItem.appendChild(priceLabel);
            priceItem.appendChild(priceValue);

            details2.appendChild(seatsItem);
            details2.appendChild(carItem);
            details2.appendChild(priceItem);

            // Actions
            const actions = document.createElement('div');
            actions.className = 'ride-actions';
            const detailBtn = document.createElement('button');
            detailBtn.className = 'detail-btn';
            detailBtn.textContent = 'Voir détails';
            detailBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = `covoiturage-detail.html?id=${safeText(ride?.id)}`;
            });
            actions.appendChild(detailBtn);

            // Assemblage de la carte
            card.appendChild(header);
            card.appendChild(details1);
            card.appendChild(details2);
            card.appendChild(actions);

            resultsContainer.appendChild(card);
        });
    }

    console.log('Page covoiturage initialisée (vue modulaire)');
}
