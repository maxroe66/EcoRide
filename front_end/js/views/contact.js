/*
Vue : contact.js
Rôle : Orchestration de la page contact. Importe le module contactModule.js et délègue la logique métier.
*/


// Imports
// - submitContactForm: logique de validation + soumission (API) du formulaire de contact
// - displayError/showLoader/hideLoader: helpers UI (messages, indicateur de chargement, accessibilité)
import { submitContactForm } from '../modules/contactModule.js';
import { displayError, showLoader, hideLoader } from '../utils/utils.js';

export function initContact() {
    // 1) Récupération des éléments du DOM nécessaires
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('successMessage');
    const confirmEmailSpan = document.getElementById('confirmEmail');
    // Timer pour l'auto-disparition du bandeau de succès
    let successHideTimer = null;
    // Accessibilité: rendre le bandeau succès focusable une fois pour toutes
    if (successMessage) {
        successMessage.setAttribute('tabindex', '-1');
    }
    // Créer une zone d'erreur dédiée si absente pour ne pas impacter le bandeau de succès
    const formSection = document.querySelector('.contact-form-section');
    let errorZone = formSection?.querySelector('.error-message');
    if (!errorZone && formSection && contactForm) {
        errorZone = document.createElement('div');
        errorZone.className = 'error-message';
        errorZone.style.display = 'none';
        errorZone.setAttribute('role', 'alert');
        errorZone.setAttribute('aria-live', 'assertive');
        formSection.insertBefore(errorZone, contactForm); // au-dessus du formulaire
    }

    if (!contactForm || !submitBtn) return;

    // 2) Câblage de la soumission du formulaire
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        // 2.1) Lecture + normalisation basique
        const name = document.getElementById('contactName').value.trim();
        const email = document.getElementById('contactEmail').value.trim();
        const subject = document.getElementById('contactSubject').value;
        const message = document.getElementById('contactMessage').value.trim();
        const consent = document.getElementById('contactConsent').checked;
        // 2.2) Nettoyage des messages précédents (zone d'erreur dédiée)
        if (errorZone) {
            displayError('', { type: 'clear', container: errorZone });
        } else {
            displayError('', 'clear');
        }
        // 2.3) Feedback utilisateur: activation du loader sur le bouton
        showLoader(submitBtn);
        let result;
        try {
            result = await submitContactForm({ name, email, subject, message, consent });
        } catch (_) {
            result = { success: false, message: 'Une erreur inattendue est survenue.' };
        } finally {
            // 2.4) Fin de traitement: on masque le loader (hideLoader réactive le bouton et restaure l'état accessibilité)
            hideLoader(submitBtn);
        }

        if (result && result.success) {
            // 2.5) Affichage succès + garder le formulaire visible
            if (successMessage) {
                if (confirmEmailSpan && (result.email || result.echo_email)) {
                    confirmEmailSpan.textContent = result.email || result.echo_email;
                }
                successMessage.style.display = 'block';
                // Focus pour lecteurs d'écran + scroll doux
                try { successMessage.focus({ preventScroll: true }); } catch {}
                // Scroll doux vers le bandeau
                try { successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch {}
                // Auto-hide après 3s (et cancel si nouvelle soumission)
                if (successHideTimer) clearTimeout(successHideTimer);
                successHideTimer = setTimeout(() => {
                    successMessage.style.display = 'none';
                    if (confirmEmailSpan) confirmEmailSpan.textContent = '';
                }, 3000);
            }
            // Cacher la zone d'erreur si elle était visible (clear via util si présente)
            if (errorZone) displayError('', { type: 'clear', container: errorZone });
            // Réinitialiser le formulaire pour permettre un nouvel envoi
            if (contactForm) contactForm.reset();
        } else {
            // 2.6) Message d'erreur (retour module/backend)
            const msg = (result && result.message) ? result.message : 'Impossible d’envoyer votre message.';
            if (errorZone) {
                displayError(msg, { type: 'error', container: errorZone });
            } else {
                displayError(msg);
            }
        }
    });
}
