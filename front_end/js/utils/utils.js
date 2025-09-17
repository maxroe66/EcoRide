/*
Utils : utils.js
Rôle : fonctions utilitaires transversales pour le front EcoRide (affichage erreurs, loader, validation de base, etc.).
Importé par les modules et vues selon les besoins.
*/

// displayError: affiche un message dans une zone cible
// - compatibilité: 2ème param peut être une string ('error'|'success'|'clear') ou un objet { type, container }
// - container: Element ou sélecteur CSS. Si absent, on fallback sur la première zone présente.
export function displayError(msg, opts = 'error') {
  const isObj = typeof opts === 'object' && opts !== null;
  const type = isObj ? (opts.type || 'error') : (opts || 'error');
  const container = isObj ? opts.container : undefined;

  let zone = null;
  if (container) {
    zone = (typeof container === 'string') ? document.querySelector(container) : container;
  }
  if (!zone) {
    zone = document.querySelector('.error-message, .success-message');
  }
  if (zone) {
    // On évite de changer la classe de base quand on clear: on se contente de masquer
    if (type === 'clear') {
      zone.style.display = 'none';
      // Ne pas écraser le contenu si on veut réutiliser le markup
      // zone.textContent = '';
      return;
    }
    // Classe selon le type si la zone n'a pas déjà une classe spécifique
    if (type === 'success') {
      zone.classList.remove('error-message');
      zone.classList.add('success-message');
    } else {
      zone.classList.remove('success-message');
      zone.classList.add('error-message');
    }
    zone.textContent = msg;
    zone.style.display = msg ? 'block' : 'none';
  } else if (msg) {
    alert(msg);
  }
}

// showLoader: peut gérer le label et l'état du bouton si c'est un bouton
// - label (optionnel): si fourni, remplace le texte affiché pendant le chargement
// - si label non fourni, on peut utiliser data-loading-label sur la cible
export function showLoader(target, label) {
  if (!target) return;
  const isButton = target.tagName === 'BUTTON' || target.getAttribute('role') === 'button';

  // Gère le label du bouton pendant le chargement
  const loadingLabel = label ?? target?.dataset?.loadingLabel;
  if (isButton) {
    if (!target.dataset.originalLabel) {
      target.dataset.originalLabel = target.textContent?.trim() ?? '';
    }
    if (loadingLabel) {
      target.textContent = loadingLabel;
    }
    target.disabled = true;
    target.setAttribute('aria-busy', 'true');
  }

  // Ajout/affichage d'un indicateur visuel simple
  let loader = target.querySelector(':scope > .loader');
  if (!loader) {
    loader = document.createElement('span');
    loader.className = 'loader';
    loader.setAttribute('aria-hidden', 'true');
    loader.textContent = '⏳';
    target.appendChild(loader);
  }
  loader.style.display = 'inline-block';
}

export function hideLoader(target) {
  if (!target) return;
  const isButton = target.tagName === 'BUTTON' || target.getAttribute('role') === 'button';

  // Cache l'indicateur visuel
  let loader = target.querySelector(':scope > .loader');
  if (loader) loader.style.display = 'none';

  // Restaure label et état accessibilité pour les boutons
  if (isButton) {
    if (target.dataset.originalLabel !== undefined) {
      target.textContent = target.dataset.originalLabel;
      delete target.dataset.originalLabel;
    }
    target.disabled = false;
    target.setAttribute('aria-busy', 'false');
  }
}

// Extraire un message pertinent depuis une réponse/erreur API
export function extractApiMessage(errOrResp, fallback = 'Une erreur est survenue.') {
  try {
    if (!errOrResp) return fallback;
    // Réponse succès standardisée
    if (errOrResp && typeof errOrResp === 'object' && errOrResp.success !== undefined) {
      return errOrResp.message || fallback;
    }
    // Erreur lancée par ecoApi.handleResponse (avec payload)
    const p = errOrResp.payload || errOrResp;
    if (p && typeof p === 'object') {
      if (typeof p.message === 'string' && p.message.trim()) return p.message;
      if (typeof p.error === 'string' && p.error.trim()) return p.error;
    }
    if (typeof errOrResp.message === 'string' && errOrResp.message.trim()) return errOrResp.message;
    return fallback;
  } catch {
    return fallback;
  }
}

// Affichage unifié d'un bandeau de message (success/error/info/warning)
// - container: Element ou sélecteur. Si absent, fallback sur .error-message/.success-message
// - autoHideMs: durée avant masquage auto (null/0 pour ne pas masquer)
export function displayBanner(message, { type = 'info', container, autoHideMs = 3000 } = {}) {
  const isSuccess = type === 'success';
  const isError = type === 'error';
  const isWarning = type === 'warning';

  let zone = null;
  if (container) {
    zone = (typeof container === 'string') ? document.querySelector(container) : container;
  }
  if (!zone) zone = document.querySelector('.error-message, .success-message');
  if (!zone) return; // pas de fallback alert ici pour éviter doublon avec displayError

  // Déterminer la classe à appliquer (on réutilise les styles globaux existants)
  zone.classList.remove('error-message', 'success-message');
  if (isSuccess) zone.classList.add('success-message');
  else zone.classList.add('error-message');
  // Texte sécurisé
  zone.textContent = message || '';
  // Accessibilité
  zone.setAttribute('role', isError ? 'alert' : 'status');
  zone.setAttribute('aria-live', isError ? 'assertive' : 'polite');
  zone.style.display = message ? 'block' : 'none';
  // Focus doux en cas de succès/erreur notable
  try { zone.setAttribute('tabindex', '-1'); zone.focus({ preventScroll: true }); zone.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch {}

  if (autoHideMs && autoHideMs > 0) {
    const t = setTimeout(() => { zone.style.display = 'none'; }, autoHideMs);
    // Retourner un handle simple
    return { hide() { clearTimeout(t); zone.style.display = 'none'; } };
  }
  return { hide() { zone.style.display = 'none'; } };
}

// Encapsule un bouton avec loader pour exécuter une action async en toute sécurité
export async function withButtonLoader(buttonEl, labelDuring, action) {
  if (!buttonEl || typeof action !== 'function') { return await action?.(); }
  try {
    showLoader(buttonEl, labelDuring);
    const res = await action();
    return res;
  } finally {
    hideLoader(buttonEl);
  }
}

// Modale de confirmation accessible (Promise<boolean>)
export function openConfirmDialog({ title = 'Confirmation', message = 'Êtes-vous sûr ?', confirmLabel = 'OK', cancelLabel = 'Annuler' } = {}) {
  return new Promise((resolve) => {
    // Backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.setAttribute('role', 'presentation');

    // Dialog
    const dialog = document.createElement('div');
    dialog.className = 'modal-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    const titleId = 'confirmDialogTitle_' + Math.random().toString(36).slice(2);
    dialog.setAttribute('aria-labelledby', titleId);

    dialog.innerHTML = `
      <h3 id="${titleId}" class="modal-title">${title}</h3>
      <p class="modal-message">${message}</p>
      <div class="modal-actions">
        <button type="button" class="btn-confirm">${confirmLabel}</button>
        <button type="button" class="btn-cancel">${cancelLabel}</button>
      </div>
    `;

    backdrop.appendChild(dialog);
    document.body.appendChild(backdrop);

    const confirmBtn = dialog.querySelector('.btn-confirm');
    const cancelBtn = dialog.querySelector('.btn-cancel');

    const cleanup = (result) => {
      try { document.body.removeChild(backdrop); } catch {}
      resolve(result);
    };

    // Focus initial et trap
    let lastFocused = document.activeElement;
    try { confirmBtn.focus(); } catch {}
    const keyHandler = (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cleanup(false); if (lastFocused) try { lastFocused.focus(); } catch {} }
      if (e.key === 'Tab') {
        const focusables = dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const list = Array.from(focusables);
        if (!list.length) return;
        const first = list[0];
        const last = list[list.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    };
    backdrop.addEventListener('keydown', keyHandler);

    // Fermetures
    confirmBtn.onclick = () => cleanup(true);
    cancelBtn.onclick = () => cleanup(false);
    backdrop.addEventListener('click', (e) => { if (e.target === backdrop) cleanup(false); });
  });
}

// Envelopper une section asynchrone avec état de chargement et gestion d'erreur
export async function withAsyncSection(container, asyncFn, {
  loadingHtml = '<div class="section-loading">Chargement...</div>',
  onError,
  errorContainer,
  errorType = 'error',
} = {}) {
  if (!container) { return asyncFn(); }
  const el = (typeof container === 'string') ? document.querySelector(container) : container;
  if (!el) { return asyncFn(); }
  const prev = el.innerHTML;
  el.setAttribute('aria-busy', 'true');
  el.innerHTML = loadingHtml;
  try {
    const res = await asyncFn();
    el.removeAttribute('aria-busy');
    return res;
  } catch (err) {
    el.removeAttribute('aria-busy');
    if (typeof onError === 'function') {
      try { onError(err, el); } catch {}
    }
    // Bandeau optionnel
    if (errorContainer) {
      const msg = extractApiMessage(err, 'Erreur lors du chargement.');
      displayBanner(msg, { type: errorType, container: errorContainer, autoHideMs: 4000 });
    } else {
      // Fallback visuel: restaurer contenu précédent et afficher une ligne d'erreur
      el.innerHTML = prev;
      const p = document.createElement('p');
      p.style.color = '#b00';
      p.textContent = extractApiMessage(err, 'Erreur lors du chargement.');
      el.appendChild(p);
    }
    throw err;
  }
}
