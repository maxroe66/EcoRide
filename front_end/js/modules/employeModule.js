import { showLoader, hideLoader, displayError } from '../utils/utils.js';
import { ecoApi } from '../api/ecoApi.js';

function escapeHtml(str = '') { return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
function formatDateHeure(cov = {}) { const date = cov.date || cov.date_depart; const heure = cov.heure || cov.heure_depart; return date ? date + (heure ? ' ' + heure : '') : ''; }

// ---------------- Historique litiges (schema v2 uniquement) ----------------
export async function loadLitigesHistorique() {
  const container = document.getElementById('employeLitigesHistoriqueContainer');
  if (!container) return;
  container.textContent = 'Chargement de l\'historique des litiges...';
  try {
    showLoader(container);
    const json = await ecoApi.get('/admin/incidents/tous', { credentials: 'include' });
    hideLoader(container);
    if (!json.success || json.schema_version !== 2) { container.innerHTML = '<p>Réponse inattendue (schema_version).</p>'; return; }
    const incidents = Array.isArray(json.historique) ? json.historique : [];
    if (incidents.length === 0) { container.innerHTML = '<p>Aucun covoiturage problématique trouvé.</p>'; return; }
    let html = '<h2>🛑 Historique des covoiturages problématiques</h2>';
    html += '<table style="width:100%;border-collapse:collapse;">';
    html += '<thead><tr style="background:#ffcdd2;"><th>ID covoiturage</th><th>Date</th><th>Départ</th><th>Arrivée</th><th>Passager</th><th>Chauffeur</th><th>Description</th><th>Statut</th></tr></thead><tbody>';
    incidents.forEach(i => {
      const cov = i.covoiturage || {}; const pass = i.passager || {}; const ch = i.chauffeur || {};
      const dateStr = formatDateHeure(cov);
      html += `<tr>
        <td>${escapeHtml(cov.id ?? '-')}</td>
        <td>${escapeHtml(dateStr)}</td>
        <td>${escapeHtml(cov.lieu_depart || '-')}</td>
        <td>${escapeHtml(cov.lieu_arrivee || '-')}</td>
        <td>${escapeHtml(pass.pseudo || '')}${pass.email ? `<br><a href="mailto:${escapeHtml(pass.email)}">${escapeHtml(pass.email)}</a>`:''}</td>
        <td>${escapeHtml(ch.pseudo || '')}${ch.email ? `<br><a href="mailto:${escapeHtml(ch.email)}">${escapeHtml(ch.email)}</a>`:''}</td>
        <td style="max-width:220px;">${escapeHtml(i.description_full || i.description || '')}</td>
        <td>${escapeHtml(i.status || '-')}</td>
      </tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
  } catch (e) {
    hideLoader(container);
    displayError('Erreur lors du chargement de l\'historique des litiges.');
    container.innerHTML = '<p>Erreur lors du chargement de l\'historique des litiges.</p>';
  }
}

export async function checkEmployeSession() {
  try { showLoader(document.body); const json = await ecoApi.get('/session', { credentials: 'include' }); hideLoader(document.body); if (!json.connected || json.type_utilisateur !== 'employe') { displayError('Accès réservé aux employés.'); setTimeout(()=>{ window.location.href='connexion.html'; },1200);} } catch { hideLoader(document.body); window.location.href='connexion.html'; }
}

export async function loadAvisEnAttente() {
  const container = document.getElementById('employeAvisContainer'); if (!container) return;
  container.textContent = 'Chargement des avis en attente...';
  try {
    showLoader(container);
    const json = await ecoApi.get('/admin/avis/en-attente', { credentials: 'include' });
    hideLoader(container);
    if (!json.success || json.schema_version !== 2) { container.innerHTML = '<p>Réponse inattendue (schema_version).</p>'; return; }
    const avisList = Array.isArray(json.avis) ? json.avis : [];
    if (!avisList.length) { container.innerHTML = '<p>Aucun avis en attente.</p>'; return; }
    let html = '<h2>📝 Avis en attente de validation</h2>';
    html += '<table style="width:100%;border-collapse:collapse;">';
    html += '<thead><tr style="background:#ffe082;"><th>Note</th><th>Commentaire</th><th>Auteur</th><th>Covoiturage</th><th>Action</th></tr></thead><tbody>';
    avisList.forEach(a => {
      const avisId = a.id || a._id;
      const rating = (a.rating ?? '—');
      const incidentPresent = !!(a.incident && a.incident.present);
      const rowStyle = incidentPresent ? 'style="background:#ffebee;"' : '';
      const comment = escapeHtml(a.text || a.commentaire || '');
      const authorDisplay = escapeHtml(a.author?.display || 'Inconnu');
      const incidentBadge = incidentPresent ? `<strong>[Incident]</strong> ` : '';
      const canValidate = a.actions?.validate !== false; // true par défaut
      const canRefuse = a.actions?.refuse !== false;
      let actionsHtml = '';
      if (!canValidate && !canRefuse) {
        actionsHtml = '<button class="btn-annuler" style="background:#b71c1c;border-color:#b71c1c" disabled>⚠️ Litige</button>';
      } else {
        if (canValidate) actionsHtml += `<button class="btn-secondary" data-action="valider" data-id="${avisId}">Valider</button>`;
        if (canRefuse) actionsHtml += ` <button class="btn-secondary" data-action="refuser" data-id="${avisId}">Refuser</button>`;
      }
      html += `<tr>
        <td ${rowStyle}>${rating}</td>
        <td ${rowStyle}>${incidentBadge}${comment}</td>
        <td>${authorDisplay}</td>
        <td>${escapeHtml(String(a.covoiturage_id || ''))}</td>
        <td>${actionsHtml}</td>
      </tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
    container.querySelectorAll('button[data-action]')?.forEach(btn => {
      btn.addEventListener('click', () => validerOuRefuserAvis(btn.getAttribute('data-id'), btn.getAttribute('data-action')));
    });
  } catch (e) {
    hideLoader(container);
    displayError('Erreur lors du chargement des avis.');
    container.innerHTML = '<p>Erreur lors du chargement des avis.</p>';
  }
}

export async function validerOuRefuserAvis(avisId, action) {
  if (!confirm(action === 'valider' ? 'Valider cet avis ?' : 'Refuser cet avis ?')) return;
  const container = document.getElementById('employeAvisContainer');
  try { showLoader(container); const json = await ecoApi.post('/admin/avis/valider', { avis_id: avisId, action }); hideLoader(container); if (json.success) { displayError('Opération réussie.', 'success'); await loadAvisEnAttente(); } else { displayError(json.message || 'Erreur lors de la validation.'); } } catch { hideLoader(container); displayError('Erreur lors de la validation.'); }
}

export async function loadIncidents() {
  const wrap = document.getElementById('employeIncidentsContainer'); if (!wrap) return;
  wrap.textContent = 'Chargement des incidents...';
  try {
    showLoader(wrap);
    const json = await ecoApi.get('/admin/incidents', { credentials:'include' });
    hideLoader(wrap);
    if (!json.success || json.schema_version !== 2) { wrap.textContent = 'Réponse inattendue (schema_version).'; return; }
    const list = Array.isArray(json.incidents) ? json.incidents : [];
    if (!list.length) { wrap.innerHTML = '<p>Aucun incident en cours.</p>'; return; }
    let html = '<h2>⚠️ Incidents en cours</h2>';
    html += '<table>';
    html += '<thead><tr><th>ID covoiturage</th><th>ID incident</th><th>Covoiturage</th><th>Date</th><th>Passager</th><th>Chauffeur</th><th>Escrow</th><th>Description</th><th>Statut</th><th>Actions</th></tr></thead><tbody>';
    list.forEach(i => {
      const cov = i.covoiturage || {}; const pass = i.passager || {}; const ch = i.chauffeur || {}; const escrow = i.escrow || i.participation?.escrow || {};
      const dateStr = formatDateHeure({ date: cov.date_depart, heure: cov.heure_depart });
      const escrowStatut = escrow.statut || 'pending';
      const escrowBadge = `<span class="badge-escrow ${escapeHtml(escrowStatut.toLowerCase())}">${escapeHtml(escrowStatut)}</span>`;
      const statutIncident = escapeHtml(i.status || 'en_cours');
      const desc = escapeHtml(i.description_full || i.description || '');
      const incidentId = i.id || i.incident_id || '';
      const canRefund = i.actions?.refund === true; // on exige true explicite pour éviter action sur incident clos
      const canRelease = i.actions?.release === true;
      const disabledAttrRefund = canRefund ? '' : 'disabled';
      const disabledAttrRelease = canRelease ? '' : 'disabled';
      html += `<tr>
        <td>${escapeHtml(cov.id ?? '-')}</td>
        <td>${escapeHtml(incidentId)}</td>
        <td>${escapeHtml(cov.lieu_depart || '-') } → ${escapeHtml(cov.lieu_arrivee || '-')}</td>
        <td>${escapeHtml(dateStr)}</td>
        <td>${escapeHtml(pass.pseudo || '')}${pass.email ? `<br><a href=\"mailto:${escapeHtml(pass.email)}\">${escapeHtml(pass.email)}</a>`:''}</td>
        <td>${escapeHtml(ch.pseudo || '')}${ch.email ? `<br><a href=\"mailto:${escapeHtml(ch.email)}\">${escapeHtml(ch.email)}</a>`:''}</td>
        <td>${escrowBadge}</td>
        <td style=\"max-width:220px;\">${desc}</td>
        <td>${statutIncident}</td>
        <td class=\"actions-cell\">
          <div class=\"actions-col\">
            <button class=\"btn-secondary btn-compact\" data-incident=\"${escapeHtml(incidentId)}\" data-mode=\"refund\" ${disabledAttrRefund}>Refund</button>
            <button class=\"btn-secondary btn-compact\" data-incident=\"${escapeHtml(incidentId)}\" data-mode=\"release\" ${disabledAttrRelease}>Release</button>
          </div>
        </td>
      </tr>`;
    });
    html += '</tbody></table>';
    wrap.innerHTML = html;
    wrap.querySelectorAll('button[data-incident]')?.forEach(btn => btn.addEventListener('click', () => resolveIncident(btn.getAttribute('data-incident'), btn.getAttribute('data-mode'))));
  } catch (e) { hideLoader(wrap); displayError('Erreur chargement incidents.'); wrap.textContent = 'Erreur chargement incidents.'; }
}

export async function resolveIncident(id, mode) {
  if (!confirm(mode==='refund' ? 'Rembourser le passager ?' : 'Libérer les fonds au chauffeur ?')) return;
  const cont = document.getElementById('employeIncidentsContainer');
  try {
    showLoader(cont);
    const json = await ecoApi.post('/admin/incidents/resoudre?mode='+mode, { incident_id: id });
    hideLoader(cont);
    if (json.success) { displayError('Incident résolu ('+mode+').', 'success'); await loadIncidents(); await loadLitigesHistorique(); }
    else { displayError(json.message || 'Erreur résolution'); }
  } catch { hideLoader(cont); displayError('Erreur réseau résolution'); }
}
