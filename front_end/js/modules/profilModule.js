// Module profil : logique métier, helpers et rendu HTML
// Toutes les fonctions sont exportées pour usage dans la vue profil.js
// Refactor: utilise ecoApi + utils directement (pas d'injection), gardes DOM, XSS-safe

import { ecoApi } from '../api/ecoApi.js';
import { displayError, showLoader, hideLoader } from '../utils/utils.js';

function escapeHtml(str) {
	if (str === null || str === undefined) return '';
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

// Petite fonction utilitaire (évite la répétition du calcul de durée dans deux boucles)
export function calcDuree(dateDepart, heureDepart, heureArrivee) {
	if (!dateDepart || !heureDepart || !heureArrivee) return '';
	try {
		const dep = new Date(`${dateDepart}T${heureDepart}`);
		const arr = new Date(`${dateDepart}T${heureArrivee}`);
		const diffMin = Math.max(0, Math.round((arr - dep) / 60000));
		const h = Math.floor(diffMin / 60);
		const m = diffMin % 60;
		return ` | ⏱️ ${h}h${m.toString().padStart(2,'0')}`;
	} catch (_) {
		return '';
	}
}

// Rendu HTML covoiturage proposé
export function renderCovoituragePropose(covoiturage) {
	const dateDepart = new Date(covoiturage.date_depart).toLocaleDateString('fr-FR');
	const isAnnule = covoiturage.statut === 'annule';
	const enCours = covoiturage.statut === 'en_cours';
	const termine = covoiturage.statut === 'termine';
	const heureArr = covoiturage.heure_arrivee;
	const duree = calcDuree(covoiturage.date_depart, covoiturage.heure_depart, heureArr);
	const participantsReserves = parseInt(covoiturage.participants_reserves ?? 0, 10);
	const incidentsOuv = parseInt(covoiturage.incidents_ouverts ?? 0, 10);
	const totalPlaces = parseInt(covoiturage.nb_places ?? 0, 10);
	let placesLabel;
	if (totalPlaces <= 0) {
		placesLabel = '—';
	} else if (participantsReserves === 0) {
		placesLabel = `${totalPlaces} place${totalPlaces>1?'s':''} dispo`;
	} else if (participantsReserves >= totalPlaces) {
		placesLabel = `Complet (${participantsReserves}/${totalPlaces})`;
	} else {
		const restantes = totalPlaces - participantsReserves;
		placesLabel = `${participantsReserves}/${totalPlaces} réservée${participantsReserves>1?'s':''} (${restantes} restante${restantes>1?'s':''})`;
	}

	let boutonAction = '';
	if (isAnnule) {
		boutonAction = '<button class="btn-annuler" disabled>Annulé</button>';
	} else if (termine) {
		if (incidentsOuv > 0) {
			boutonAction = '<button class="btn-annuler" style="background:#b71c1c;border-color:#b71c1c" disabled>⚠️ Litige</button>';
		} else {
			boutonAction = '<button class="btn-annuler" disabled>Terminé</button>';
		}
	} else if (enCours) {
		boutonAction = `<button class="btn-terminer" data-action="terminer" data-id="${escapeHtml(covoiturage.covoiturage_id)}">⏳ En cours</button>`;
	} else {
		boutonAction = `<button class="btn-demarrer" data-action="demarrer" data-id="${escapeHtml(covoiturage.covoiturage_id)}">🚀 Démarrer</button>
			<button class="btn-annuler" data-action="annuler" data-id="${escapeHtml(covoiturage.covoiturage_id)}" data-type="covoiturage">Annuler</button>`;
	}

	const ecoBadge = covoiturage.voiture_ecologique == 1 
		? ' | <span class="eco-badge" title="Véhicule écologique">🌱 Écologique</span>' 
		: '';
	const fadedClass = (isAnnule || termine) ? ' historique-item--faded' : '';
	const incidentBadge = incidentsOuv > 0 ? ` | <span class="incident-badge" title="Incident en cours">⚠️ Incident (${incidentsOuv})</span>` : '';

	return `
		<div class="historique-item${fadedClass}">
			<div class="historique-info">
				<div class="historique-route">${escapeHtml(covoiturage.lieu_depart)} → ${escapeHtml(covoiturage.lieu_arrivee)}</div>
				<div class="historique-details">
					📅 ${dateDepart} à ${escapeHtml(covoiturage.heure_depart)}${heureArr ? ` → ${escapeHtml(heureArr)}` : ''}${duree} | 
					👥 ${placesLabel} | 
					💰 ${covoiturage.prix_personne}€ | 
					🚙 ${escapeHtml(covoiturage.marque)} ${escapeHtml(covoiturage.modele)}${ecoBadge}${incidentBadge}
					${isAnnule ? ' | ❌ ANNULÉ' : ''}
					${enCours ? ' | 🚗 EN COURS' : ''}
					${termine ? ' | ✅ TERMINÉ' : ''}
				</div>
			</div>
			<div class="historique-actions">
				${boutonAction}
			</div>
		</div>
	`;
}

export function renderParticipation(participation) {
	const dateDepart = new Date(participation.date_depart).toLocaleDateString('fr-FR');
	const isAnnule = participation.statut_participation === 'annulee';
	const enAttenteValidation = participation.statut_participation === 'en_attente_validation';
	const validee = participation.statut_participation === 'validee';
	const escrowPending = participation.escrow_statut === 'pending';
	const eco = (participation.voiture_ecologique == 1) || (participation.covoiturage_ecologique == 1);
	const ecoBadge = eco ? ' | <span class="eco-badge" title="Trajet écologique">🌱 Écologique</span>' : '';
	const heureArr = participation.heure_arrivee;
	const duree = calcDuree(participation.date_depart, participation.heure_depart, heureArr);

	let boutonAction = '';
	const estProbleme = participation.statut_participation === 'probleme';
	if (isAnnule) {
		boutonAction = '<button class="btn-annuler" disabled>Annulé</button>';
	} else if (validee) {
		boutonAction = '<button class="btn-annuler" disabled>Validé</button>';
	} else if (estProbleme) {
		boutonAction = '<button class="btn-annuler" disabled>Problème</button>';
	} else if (enAttenteValidation) {
		boutonAction = `<button class="btn-valider" data-action="confirmer" data-id="${escapeHtml(participation.participation_id)}">✅ Confirmer trajet</button>`;
	} else {
		boutonAction = `<button class="btn-annuler" data-action="annuler" data-id="${escapeHtml(participation.participation_id)}" data-type="participation">Annuler</button>`;
	}

	const escrowBadge = (escrowPending && enAttenteValidation) ? ' | <span style="color:#b26f00;font-weight:bold;">(fonds bloqués)</span>' : '';
	const fadedClass = (isAnnule || validee) ? ' historique-item--faded' : '';

	return `
		<div class="historique-item${fadedClass}">
			<div class="historique-info">
				<div class="historique-route">${escapeHtml(participation.lieu_depart)} → ${escapeHtml(participation.lieu_arrivee)}</div>
				<div class="historique-details">
					📅 ${dateDepart} à ${escapeHtml(participation.heure_depart)}${heureArr ? ` → ${escapeHtml(heureArr)}` : ''}${duree} | 
					👥 ${escapeHtml(participation.places_reservees)} place(s) | 
					💰 ${escapeHtml(participation.prix_personne)}€/place${ecoBadge}
					${isAnnule ? ' | ❌ ANNULÉ' : ''}
					${enAttenteValidation ? ' | ⏳ À CONFIRMER' : ''}
					${validee ? ' | ✅ VALIDÉ' : ''}
					${escrowBadge}
				</div>
			</div>
			<div class="historique-actions">
				${boutonAction}
			</div>
		</div>
	`;
}

// Fonctions métier principales
export async function chargerProfil() {
	try {
		const sessionData = await ecoApi.get('/session', { credentials: 'include' });
		if (!sessionData.connected) {
			const lm = document.getElementById('loadingMessage');
			if (lm) lm.style.display = 'none';
			displayError('Session expirée ou non connectée.');
			return;
		}
		const userData = await ecoApi.get('/utilisateur/profil', { credentials: 'include' });
		if (userData.success && userData.user) {
			afficherProfil(userData.user);
			await chargerVehiculesProfil();
			await chargerHistoriqueCovoiturages();
		} else {
			displayError(userData.message || 'Erreur lors du chargement du profil');
			const lm = document.getElementById('loadingMessage');
			if (lm) lm.style.display = 'none';
			return;
		}
	} catch (error) {
		console.error('Erreur:', error);
		const lm = document.getElementById('loadingMessage');
		if (lm) lm.style.display = 'none';
		displayError('Erreur lors du chargement du profil.');
	}
}

export async function chargerVehiculesProfil() {
	const section = document.getElementById('vehiculesSection');
	if (!section) return;
	section.innerHTML = '<div class="loading">Chargement des véhicules...</div>';
	try {
		const data = await ecoApi.get('/vehicules/profil', { credentials: 'include' });
		if (!data.success) {
			displayError(data.message || 'Impossible de charger les véhicules');
			section.innerHTML = '';
			afficherBoutonCovoiturage(false);
			return;
		}
		if (!data.vehicules || data.vehicules.length === 0) {
			section.innerHTML = '<div class="loading">Aucun véhicule enregistré.</div>';
			afficherBoutonCovoiturage(false);
			return;
		}
		let html = '<h3>🚗 Mes véhicules</h3>';
		html += '<table class="table-vehicules">';
		html += '<tr><th>Modèle</th><th>Marque</th><th>Immatriculation</th><th>Énergie</th><th>Places</th><th>Écologique</th><th>Couleur</th><th>1ère immat.</th></tr>';
		for (const v of data.vehicules) {
			html += `<tr>
				<td data-label="Modèle">${escapeHtml(v.modele)}</td>
				<td data-label="Marque">${escapeHtml(v.marque)}</td>
				<td data-label="Immatriculation">${escapeHtml(v.immatriculation)}</td>
				<td data-label="Énergie">${escapeHtml(v.energie)}</td>
				<td data-label="Places">${escapeHtml(v.nb_places)}</td>
				<td data-label="Écologique">${v.est_ecologique == 1 ? 'Oui' : 'Non'}</td>
				<td data-label="Couleur">${escapeHtml(v.couleur || '-')}</td>
				<td data-label="1ère immat.">${(v.date_premiere_immatriculation ? new Date(v.date_premiere_immatriculation).toLocaleDateString('fr-FR') : '-')}</td>
			</tr>`;
		}
		html += '</table>';
		section.innerHTML = html;
		afficherBoutonCovoiturage(true);
	} catch (e) {
		displayError('Erreur lors du chargement des véhicules.');
		section.innerHTML = '';
		afficherBoutonCovoiturage(false);
	}
}

export function afficherBoutonCovoiturage(aDesVehicules) {
	const btnGroup = document.querySelector('.btn-group');
	const btnCovoiturage = document.getElementById('btnAjouterCovoiturage');
	if (!btnGroup) return;
	if (aDesVehicules && !btnCovoiturage) {
		const nouveauBtn = document.createElement('button');
		nouveauBtn.id = 'btnAjouterCovoiturage';
		nouveauBtn.className = 'btn btn-primary';
		nouveauBtn.onclick = () => window.location.href = 'ajouter-covoiturage.html';
		nouveauBtn.innerHTML = '🚗 Ajouter un covoiturage';
		btnGroup.insertBefore(nouveauBtn, btnGroup.firstChild);
	} else if (!aDesVehicules && btnCovoiturage) {
		btnCovoiturage.remove();
	}
}

export function afficherProfil(user) {
	const setText = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };
	const show = (id) => { const el = document.getElementById(id); if (el) el.style.display = 'block'; };
	const hide = (id) => { const el = document.getElementById(id); if (el) el.style.display = 'none'; };
	hide('loadingMessage');
	show('profileContent');
	const initiales = ((user.nom?.charAt(0) || '') + (user.prenom?.charAt(0) || '')).toUpperCase();
	setText('userAvatar', initiales);
	setText('userName', `${escapeHtml(user.prenom || '')} ${escapeHtml(user.nom || '')}`);
	setText('userEmail', user.email || '');
	setText('userCredit', (user.credit != null ? user.credit : '0'));
	setText('infoNom', user.nom || '');
	setText('infoPrenom', user.prenom || '');
	setText('infoEmail', user.email || '');
	(function formatDateInscription(raw){
		const id = 'infoDateInscription';
		if(!raw){ setText(id,'—'); return; }
		let d = new Date(raw);
		if(isNaN(d.getTime())) {
			const m = /^([0-9]{4})-([0-9]{2})-([0-9]{2})/.exec(raw);
			if (m) {
				d = new Date(m[1]+'-'+m[2]+'-'+m[3]+'T00:00:00');
			}
		}
		if(isNaN(d.getTime())) { setText(id,'—'); return; }
		setText(id, d.toLocaleDateString('fr-FR'));
	})(user.date_inscription || user.date_creation || user.dateCreation || user.date_inscription_creation);
	setText('statsCovoiturages', user.nb_covoiturages || '0');
	setText('statsParticipations', user.nb_participations || '0');
	const economiesCO2 = Math.round((user.nb_participations || 0) * 2.5);
	setText('statsEconomies', `${economiesCO2} kg CO2 économisés`);
	const statutDiv = document.getElementById('userStatut');
	const btnDiv = document.getElementById('btnChauffeurContainer');
	if (btnDiv) btnDiv.innerHTML = '';
	if (user.is_chauffeur) {
		const btn = document.createElement('button');
		btn.className = 'btn btn-primary';
		btn.textContent = 'Ajouter un véhicule';
		btn.addEventListener('click', () => { window.location.href = 'espace-utilisateur.html'; });
		if (btnDiv) btnDiv.appendChild(btn);
		afficherPreferences(user);
	} else {
		if (statutDiv) statutDiv.textContent = 'Statut : Utilisateur';
		const btn = document.createElement('button');
		btn.className = 'btn btn-primary';
		btn.textContent = 'Devenir chauffeur';
		btn.addEventListener('click', () => { window.location.href = 'espace-utilisateur.html'; });
		if (btnDiv) btnDiv.appendChild(btn);
	}
	const passagerContainer = document.getElementById('btnPassagerContainer');
	const passagerMsg = document.getElementById('passagerMessage');
	const userId = user.utilisateur_id || user.id || null;
	if (userId) {
		const storageKey = 'ecoride_passager_done_' + userId;
		try {
			if (localStorage.getItem('ecoride_passager_done') && !localStorage.getItem(storageKey)) {
				localStorage.removeItem('ecoride_passager_done');
			}
		} catch (_) {}
		let alreadyPassager = false;
		try { alreadyPassager = localStorage.getItem(storageKey) === '1'; } catch(_) {}
		if (alreadyPassager && passagerMsg) {
			passagerMsg.remove();
		}
		if (passagerContainer && !user.is_passager && !alreadyPassager) {
			const btn = document.createElement('button');
			btn.className = 'btn btn-secondary';
			btn.id = 'btnDevenirPassager';
			btn.textContent = 'Devenir passager';
			btn.addEventListener('click', () => {
				btn.remove();
				if (passagerMsg) {
					passagerMsg.style.display = 'block';
					setTimeout(() => { if (passagerMsg) passagerMsg.remove(); }, 3000);
				}
				try { localStorage.setItem(storageKey,'1'); } catch(_) {}
			});
			passagerContainer.appendChild(btn);
		} else if (passagerMsg && !alreadyPassager) {
			passagerMsg.style.display = 'none';
		}
	}
}

export async function chargerHistoriqueCovoiturages() {
    const historiqueContent = document.getElementById('historiqueContent');
    if (!historiqueContent) return;
    try {
        const data = await ecoApi.get('/utilisateur/historique', { credentials: 'include' });
        if (!data.success) {
            displayError(data.message || 'Erreur lors du chargement de l\'historique');
            historiqueContent.innerHTML = '';
            return;
        }
        let html = '';
        if (data.covoiturages_proposes && data.covoiturages_proposes.length > 0) {
            html += '<h4 style="color: #4A7C59; margin: 20px 0 15px 0;">🚗 Mes covoiturages proposés (' + data.covoiturages_proposes.length + ')</h4>';
            html += data.covoiturages_proposes.map(renderCovoituragePropose).join('');
        }
        if (data.participations && data.participations.length > 0) {
            html += '<h4 style="color: #4A7C59; margin: 20px 0 15px 0;">🎯 Mes participations (' + data.participations.length + ')</h4>';
            html += data.participations.map(renderParticipation).join('');
        }
        if ((!data.covoiturages_proposes || data.covoiturages_proposes.length === 0) && (!data.participations || data.participations.length === 0)) {
            html = '<div class="historique-vide">Aucun covoiturage dans votre historique pour le moment.</div>';
        }
        historiqueContent.innerHTML = html;
    } catch (error) {
        console.error('Erreur lors du chargement de l\'historique:', error);
        displayError('Erreur lors du chargement de l\'historique.');
        historiqueContent.innerHTML = '';
    }
}

export async function demarrerCovoiturage(covoiturage_id) {
	const confirmation = confirm('Êtes-vous sûr de vouloir démarrer ce covoiturage ?');
	if (!confirmation) {
		displayError('Démarrage annulé par l\'utilisateur.');
		return;
	}
	try {
		const payload = { covoiturage_id };
		const result = await ecoApi.post('/covoiturages/demarrer', payload);
		if (result.success) {
			displayError(result.message || 'Covoiturage démarré.', 'success');
			chargerHistoriqueCovoiturages();
		} else {
			displayError(result.message || 'Erreur lors du démarrage du covoiturage');
		}
	} catch (error) {
		console.error('Erreur lors du démarrage:', error);
		displayError('Erreur lors du démarrage du covoiturage.');
	}
}

export async function terminerCovoiturage(covoiturage_id) {
	const confirmation = confirm('Êtes-vous sûr d\'être arrivé à destination ? Les participants recevront une notification pour valider le trajet.');
	if (!confirmation) {
		displayError('Clôture annulée par l\'utilisateur.');
		return;
	}
	try {
		let loader = document.getElementById('ecoride-finish-loader');
		if (!loader) {
			loader = document.createElement('div');
			loader.id = 'ecoride-finish-loader';
			loader.style.position = 'fixed';
			loader.style.top = 0;
			loader.style.left = 0;
			loader.style.right = 0;
			loader.style.bottom = 0;
			loader.style.background = 'rgba(0,0,0,0.45)';
			loader.style.display = 'flex';
			loader.style.alignItems = 'center';
			loader.style.justifyContent = 'center';
			loader.style.zIndex = 9999;
			loader.innerHTML = '<div style="background:#fff;padding:25px 30px;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,0.25);font-family:system-ui,Arial,sans-serif;max-width:360px;text-align:center;">' +
				'<div style="font-size:32px;animation:ecorideSpin 1s linear infinite;display:inline-block;">🚗</div>' +
				'<h3 style="margin:10px 0 8px 0;color:#2f5f3a;font-size:1.1rem;">Clôture du covoiturage…</h3>' +
				'<p style="margin:0;color:#444;font-size:0.9rem;line-height:1.3;">Envoi des notifications aux participants, merci de patienter.</p>' +
				'<style>@keyframes ecorideSpin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}</style>' +
			'</div>';
			document.body.appendChild(loader);
		} else {
			loader.style.display = 'flex';
		}
		const payload = { covoiturage_id };
		const result = await ecoApi.post('/covoiturages/terminer', payload);
		if (loader) loader.style.display = 'none';
		if (result.success) {
			displayError(result.message || 'Covoiturage terminé.', 'success');
			chargerHistoriqueCovoiturages();
		} else {
			displayError(result.message || 'Erreur lors de la clôture du covoiturage');
		}
	} catch (error) {
		console.error('Erreur lors de la terminaison:', error);
		displayError('Erreur lors de la clôture du covoiturage.');
	}
}

export async function confirmerTrajet(participation_id) {
	const modal = document.createElement('div');
	modal.className = 'modal-overlay';
	modal.innerHTML = `
		<div class="modal-box">
			<h3>Validation du trajet</h3>
			<div class="modal-section">
				<label class="modal-label">Comment s'est passé le trajet ?</label>
				<label class="modal-label">
					<input type="radio" name="validation" value="bien" style="margin-right:8px;">✅ Tout s'est bien passé
				</label>
				<label class="modal-label">
					<input type="radio" name="validation" value="mal" style="margin-right:8px;">❌ Il y a eu des problèmes
				</label>
			</div>
			<div id="avisSection" class="modal-section" style="display:none;">
				<label class="modal-sub-label">Note (1-5 étoiles) :</label>
				<select id="noteSelect" class="modal-select">
					<option value="5">⭐⭐⭐⭐⭐ Excellent</option>
					<option value="4">⭐⭐⭐⭐ Très bien</option>
					<option value="3">⭐⭐⭐ Bien</option>
					<option value="2">⭐⭐ Moyen</option>
					<option value="1">⭐ Mauvais</option>
				</select>
				<div id="commentaireWrapper">
					<label class="modal-sub-label">Commentaire :</label>
					<textarea id="commentaireText" class="modal-textarea modal-textarea--small" placeholder="Partagez votre expérience..."></textarea>
				</div>
			</div>
			<div id="problemeSection" class="modal-section" style="display:none;">
				<label class="modal-sub-label">Décrivez le problème :</label>
				<textarea id="problemeText" class="modal-textarea modal-textarea--large" placeholder="Expliquez ce qui s'est mal passé..."></textarea>
			</div>
			<div class="modal-actions">
				<button class="btn-close" id="btnAnnulerModal">Annuler</button>
				<button class="btn btn-primary" id="btnValiderModal">Valider</button>
			</div>
		</div>`;
	document.body.appendChild(modal);
	modal.querySelectorAll('input[name="validation"]').forEach(radio => {
		radio.addEventListener('change', function() {
			const avisSection = modal.querySelector('#avisSection');
			const problemeSection = modal.querySelector('#problemeSection');
			const commentaireWrap = avisSection.querySelector('#commentaireWrapper');
			if (this.value === 'bien') {
				avisSection.style.display = 'block';
				problemeSection.style.display = 'none';
				if (commentaireWrap) commentaireWrap.style.display = 'block';
			} else {
				avisSection.style.display = 'block';
				problemeSection.style.display = 'block';
				if (commentaireWrap) commentaireWrap.style.display = 'none';
			}
		});
	});
	// Ajout des event listeners JS modernes pour les boutons de la modale
	const btnAnnuler = modal.querySelector('#btnAnnulerModal');
	if (btnAnnuler) {
		btnAnnuler.addEventListener('click', function() {
			modal.remove();
		});
	}
	const btnValider = modal.querySelector('#btnValiderModal');
	if (btnValider) {
		btnValider.addEventListener('click', () => {
			soumettreConfirmation(participation_id);
		});
	}
}

export async function soumettreConfirmation(participation_id) {
	const modal = document.querySelector('.modal-overlay');
	if (!modal) {
		alert('Erreur interne: fenêtre de validation introuvable. Réessayez.');
		return;
	}
	const validation = modal.querySelector('input[name="validation"]:checked');
	const btnValider = modal.querySelector('#btnValiderModal');
	if (!validation) {
		displayError('Veuillez sélectionner une option');
		return;
	}
	const data = { participation_id };
	if (validation.value === 'bien') {
		data.ok = true;
		data.note = modal.querySelector('#noteSelect').value;
		data.commentaire = modal.querySelector('#commentaireText').value;
	} else {
		data.ok = false;
		data.probleme = modal.querySelector('#problemeText').value;
		if (!data.probleme.trim()) { displayError('Veuillez décrire le problème'); return; }
		data.note = modal.querySelector('#noteSelect').value;
	}
	if (btnValider) btnValider.disabled = true;
	try {
		const result = await ecoApi.post('/covoiturages/confirmer', data);
		if (result.success) {
			displayError(result.message || 'Trajet confirmé.', 'success');
			if (modal) modal.remove();
			afreshCredit();
			chargerHistoriqueCovoiturages();
			// Forcer le rechargement complet de la page pour garantir l’actualisation
			setTimeout(() => { window.location.reload(); }, 600);
		} else {
			displayError(result.message || 'Erreur lors de la confirmation du trajet');
			if (btnValider) btnValider.disabled = false;
		}
	} catch (error) {
		console.error('Erreur lors de la confirmation:', error);
		displayError('Erreur lors de la confirmation du trajet.');
		if (btnValider) btnValider.disabled = false;
	}
}

async function afreshCredit() {
	try {
		const d = await ecoApi.get('/utilisateur/credit', { credentials: 'include' });
		if (d.success && typeof d.credit !== 'undefined') {
			const el = document.getElementById('userCredit');
			if (el) el.textContent = d.credit;
		}
	} catch (_) { /* silencieux */ }
}

export async function annulerCovoiturage(type, id) {
	const confirmation = confirm(
		type === 'covoiturage' 
			? 'Êtes-vous sûr de vouloir annuler ce covoiturage ?' 
			: 'Êtes-vous sûr de vouloir annuler cette participation ?'
	);
	if (!confirmation) {
		displayError('Annulation refusée par l\'utilisateur.');
		return;
	}
	try {
		const payload = { type, id };
		const result = await ecoApi.post('/covoiturages/annuler', payload);
		if (result.success) {
			displayError(result.message || 'Annulation réussie.', 'success');
			chargerHistoriqueCovoiturages();
		} else {
			displayError(result.message || 'Erreur lors de l\'annulation');
		}
	} catch (error) {
		console.error('Erreur lors de l\'annulation:', error);
		displayError('Erreur lors de l\'annulation.');
	}
}

export function afficherPreferences(user) {
	const preferencesDiv = document.getElementById('preferencesDisplay');
	if (!preferencesDiv) return;
	if (user.preference_fumeur || user.preference_animaux) {
		preferencesDiv.style.display = 'block';
		const fumeurText = user.preference_fumeur === 'accepte' ? 'Accepte les fumeurs' : 'Refuse les fumeurs';
		const fumeurEl = document.getElementById('infoPrefFumeur');
		if (fumeurEl) fumeurEl.textContent = fumeurText;
		const animauxText = user.preference_animaux === 'accepte' ? 'Accepte les animaux' : 'Refuse les animaux';
		const animauxEl = document.getElementById('infoPrefAnimaux');
		if (animauxEl) animauxEl.textContent = animauxText;
		if (user.autres_preferences && user.autres_preferences.trim() !== '') {
			const row = document.getElementById('autresPreferencesRow');
			const info = document.getElementById('infoAutresPreferences');
			if (row) row.style.display = 'flex';
			if (info) info.textContent = user.autres_preferences;
		}
	}
}
