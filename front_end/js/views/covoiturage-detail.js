// Vue : covoiturage-detail.js
// Rôle: Orchestration de la page de détail d'un covoiturage.
// - Charge les informations du trajet et les avis (validés + historique chauffeur)
// - Gère la participation avec confirmation, bandeaux de messages et flash cross-page
// - Utilise des helpers communs (withAsyncSection, withButtonLoader, displayBanner, openConfirmDialog)
import { withButtonLoader, displayBanner, extractApiMessage, openConfirmDialog, withAsyncSection } from '../utils/utils.js';
import {
	getAvisValides,
	getRideDetails,
	getChauffeurReviews,
	checkUserSession,
	participerCovoiturage,
	formatDate
} from '../modules/covoiturageDetailModule.js';

export function initCovoiturageDetail() {
	// Point d'entrée de la vue – appelée par main.js via data-page.
	// Met en place les listeners, zones de messages et lance les chargements asynchrones.
	loadAvisValides();
	let chauffeurReviews = [];

	 // Zone de messages (succès/erreur) dédiée près du bouton "Participer"
	 // - ARIA: role=status + aria-live=polite pour une annonce non intrusive
	 // - Insérée dynamiquement si absente dans le HTML
	 let messageZone = document.getElementById('participationMessages');
	 if (!messageZone) {
		 const partBtnHook = document.getElementById('participateBtn');
		 if (partBtnHook) {
			 messageZone = document.createElement('div');
			 messageZone.id = 'participationMessages';
			 messageZone.className = 'error-message';
			 messageZone.style.display = 'none';
			 messageZone.setAttribute('role', 'status');
			 messageZone.setAttribute('aria-live', 'polite');
			 partBtnHook.parentElement?.insertBefore(messageZone, partBtnHook);
		 }
	 }

	// Bouton retour (progressive enhancement) – remplace un éventuel lien statique
	const backBtn = document.getElementById('backBtn');
	if (backBtn) {
		backBtn.addEventListener('click', function(e) {
			e.preventDefault();
			history.back();
		});
	}

	async function loadAvisValides() {
		// Charge les avis validés du covoiturage avec un état de chargement et une gestion d'erreur standardisée
		const avisContainer = document.getElementById('avisValidesContainer');
		await withAsyncSection(avisContainer, async () => {
			const avis = await getAvisValides();
			afficherAvisValides(Array.isArray(avis) ? avis : []);
		}, {
			loadingHtml: '<div class="reviews-loading">Chargement...</div>',
			onError: () => {
				if (avisContainer) avisContainer.innerHTML = '<p style="color:#b00;">Impossible de charger les avis validés.</p>';
			}
		});
	}

	function afficherAvisValides(avisList) {
		// Affiche la liste des avis validés.
		// Hypothèse: les valeurs renvoyées sont déjà sûres/échappées côté backend.
		// Si besoin d'un durcissement XSS, préférer créer des éléments et utiliser textContent.
		const avisContainer = document.getElementById('avisValidesContainer');
		if (!avisContainer) return;
		if (avisList.length === 0) {
			avisContainer.innerHTML = '<p>Aucun avis validé pour ce covoiturage.</p>';
			return;
		}
		avisContainer.innerHTML = avisList.map(avis => `
			<div class="review-item">
				<div class="review-header">
					<span class="review-author">${avis.author || ''}</span>
					<span class="review-rating">⭐ ${avis.rating || ''}/5</span>
				</div>
				<div class="review-text">${avis.text || ''}</div>
			</div>
		`).join('');
	}

	const urlParams = new URLSearchParams(window.location.search);
	const rideId = urlParams.get('id');
	// Déclenche le chargement des détails du covoiturage identifié par l'URL
	loadRideDetails(rideId);
	const partBtn = document.getElementById('participateBtn');
	// Écouteur du bouton "Participer"
	if (partBtn) partBtn.addEventListener('click', handleParticipation);

	async function loadRideDetails(rideId) {
		// Charge les détails principaux et, en cascade, l'historique des avis du chauffeur.
		// Utilise withAsyncSection pour afficher un skeleton et gérer les erreurs.
		const container = document.getElementById('detailContainer');
		await withAsyncSection(container, async () => {
			const { ride, error } = await getRideDetails(rideId);
			if (error || !ride) {
				if (container) container.innerHTML = `<h2>${error || 'Covoiturage introuvable'}</h2>`;
				return;
			}
			displayRideDetails(ride);
			// Charger avis chauffeur
			const blocAvisCh = document.getElementById('historiqueAvisChauffeur');
			await withAsyncSection(blocAvisCh, async () => {
				chauffeurReviews = await getChauffeurReviews(rideId);
				renderChauffeurReviews();
			}, {
				loadingHtml: '<div class="reviews-loading">Chargement...</div>',
				onError: () => renderChauffeurReviews(true)
			});
		}, {
			loadingHtml: '<div class="detail-loading">Chargement des détails...</div>',
			onError: () => { if (container) container.innerHTML = '<h2>Erreur lors du chargement des détails</h2>'; }
		});
	}



	function displayRideDetails(ride) {
		// Construit et injecte le HTML des détails principaux
		// Note: formatDate est fourni par le module de données, pour centraliser la logique de formatage
		document.getElementById('detailContainer').innerHTML = `
			<div class="detail-main">
				<div class="detail-header">
					<div class="route-info">${ride.departure} → ${ride.arrival}</div>
					<div class="trip-date">${formatDate(ride.date)}</div>
				</div>
				<div class="driver-section">
					<h3>👤 Chauffeur</h3>
					<div class="driver-card">
						<div class="driver-avatar">${ride.driver.initials}</div>
						<div class="driver-info">
							<h4>${ride.driver.pseudo}</h4>
							<div class="driver-rating">⭐ ${ride.driver.rating}/5</div>
							${ride.driver.isEcological ? '<span class="eco-badge">🌱 Écologique</span>' : ''}
						</div>
					</div>
				</div>
				<div class="vehicle-section">
					<h3>🚗 Véhicule</h3>
					<div class="vehicle-info">
						<div class="vehicle-details">
							<div class="vehicle-item"><span class="label">Marque :</span><span class="value">${ride.vehicle.brand}</span></div>
							<div class="vehicle-item"><span class="label">Modèle :</span><span class="value">${ride.vehicle.model}</span></div>
							<div class="vehicle-item"><span class="label">Couleur :</span><span class="value">${ride.vehicle.color}</span></div>
							<div class="vehicle-item"><span class="label">Énergie :</span><span class="value">${ride.vehicle.energy}</span></div>
							<div class="vehicle-item"><span class="label">Date de première immatriculation :</span><span class="value">${ride.vehicle.firstRegistrationDate ? new Date(ride.vehicle.firstRegistrationDate).toLocaleDateString('fr-FR') : '—'}</span></div>
						</div>
					</div>
				</div>
				<div class="preferences-section">
					<h3>⚙️ Préférences</h3>
					<div class="preferences-list">
						${ride.preferences.map(pref => `<div class="preference-item">${pref}</div>`).join('')}
					</div>
				</div>
			</div>
			<div class="detail-sidebar">
				<div class="time-card">
					<h3>🕐 Horaires</h3>
					<div class="time-details">
						<div class="time-item"><div class="label">Départ</div><div class="value">${ride.departureTime}</div></div>
						<div class="time-item"><div class="label">Arrivée</div><div class="value">${ride.arrivalTime}</div></div>
						<div class="time-item"><div class="label">Durée</div><div class="value">${ride.duration}</div></div>
						<div class="time-item"><div class="label">Prix</div><div class="value">${ride.price}€</div></div>
					</div>
				</div>
				<div class="reviews-card" id="historiqueAvisChauffeur">
					<h3>💬 Avis chauffeur (historique)</h3>
					<div class="reviews-loading">Chargement...</div>
				</div>
			</div>
		`;
		// Mise à jour ciblée de certains éléments dynamiques
		document.getElementById('availableSeats').textContent = ride.availableSeats;
		document.getElementById('tripPrice').textContent = ride.price + '€';
	}

	function renderChauffeurReviews(erreur = false) {
		// Rend l'historique d'avis pour le chauffeur.
		// Affiche une erreur lisible si le chargement a échoué ou si la liste est vide.
		const bloc = document.getElementById('historiqueAvisChauffeur');
		if (!bloc) return;
		if (erreur) {
			bloc.innerHTML = '<h3>💬 Avis chauffeur (historique)</h3><p style="color:#b00;">Impossible de charger les avis.</p>';
			return;
		}
		if (!chauffeurReviews.length) {
			bloc.innerHTML = '<h3>💬 Avis chauffeur (historique)</h3><p>Aucun avis historique.</p>';
			return;
		}
		bloc.innerHTML = '<h3>💬 Avis chauffeur (historique)</h3>' + chauffeurReviews.map(function (r) {
			var dateAff = r.date ? new Date(r.date).toLocaleDateString('fr-FR') : '';
			return '<div class="review-item">'
				+ '<div class="review-header">'
				+ '<span class="review-author">' + (r.author || '') + '</span>'
				+ '<span class="review-rating">⭐ ' + (r.rating || '') + '/5</span>'
				+ '</div>'
				+ '<div class="review-text">' + (r.text || '') + '</div>'
				+ '<div class="review-date">' + dateAff + '</div>'
				+ '</div>';
		}).join('');
	}

	async function handleParticipation() {
		// Vérifie la session utilisateur avant d'autoriser la participation.
		// En cas de non-connexion: affiche un bandeau d'erreur local, puis redirige vers la page de connexion.
		const isLoggedIn = await checkUserSession();
		if (!isLoggedIn) {
			displayBanner('Veuillez vous connecter pour participer à ce covoiturage.', { type: 'error', container: messageZone || '#participationMessages' });
			window.location.href = 'connexion.html';
			return;
		}
		showConfirmationModal();
	}

	function showConfirmationModal() {
		// Ouvre un dialogue de confirmation accessible avant de lancer la participation.
		// Retourne une promesse résolue à true/false selon le choix utilisateur.
		openConfirmDialog({
			title: 'Confirmer la participation',
			message: 'Êtes-vous sûr de vouloir utiliser vos crédits pour participer à ce covoiturage ?',
			confirmLabel: 'Oui, je confirme',
			cancelLabel: 'Annuler'
		}).then((ok) => { if (ok) processParticipation(); });
	}

	// checkUserSession délégué au module

	async function processParticipation() {
		// Déroulé de la participation:
		// 1) Affiche un état de traitement sur le bouton via withButtonLoader
		// 2) Appelle le service participerCovoiturage
		// 3) En cas de succès: stocke un flash message et redirige vers la liste
		// 4) En cas d'échec: affiche un bandeau d'erreur (message backend propagé)
		const btn = document.getElementById('participateBtn');
		await withButtonLoader(btn, 'Traitement...', async () => {
			const urlParams = new URLSearchParams(window.location.search);
			const covoiturage_id = urlParams.get('id');
			const result = await participerCovoiturage(covoiturage_id);
			if (result && result.success) {
				const msg = result.message || 'Participation enregistrée.';
				// Stocker un flash message pour la page suivante
				try { sessionStorage.setItem('flash_success', msg); } catch {}
				// Redirection immédiate (le bandeau sera affiché sur la page d'arrivée)
				window.location.replace('covoiturage.html');
			} else {
				const msg = (result && result.message) ? result.message : 'Action impossible';
				displayBanner(msg, { type: 'error', container: messageZone || '#participationMessages' });
			}
		}).catch(err => {
			console.error('Erreur lors de la participation :', err);
			const msg = extractApiMessage(err, 'Erreur lors de la participation. Veuillez réessayer.');
			displayBanner(msg, { type: 'error', container: messageZone || '#participationMessages' });
		});
	}

	// formatDate importé depuis le module
}
