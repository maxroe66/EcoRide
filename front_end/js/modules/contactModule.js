/*
Module : contactModule.js
Rôle : logique métier pour la gestion du formulaire de contact (validation, soumission, helpers).
Importé par la vue contact.js.
*/

import { ecoApi } from '../api/ecoApi.js';

export function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

async function ensureCsrfContact() {
    await ecoApi.ensureCsrf();
}

export async function submitContactForm({ name, email, subject, message, consent }) {
    // Normalisation (trim) pour éviter les faux négatifs.
    name = (name || '').trim();
    email = (email || '').trim();
    subject = (subject || '').trim();
    message = (message || '').trim();

    // Validation côté client (défensive : le backend revalide).
    if (!name || !email || !subject || !message) {
        return { success: false, message: 'Veuillez remplir tous les champs obligatoires' };
    }
    if (!consent) {
        return { success: false, message: 'Veuillez accepter le traitement de votre message.' };
    }
    if (!isValidEmail(email)) {
        return { success: false, message: 'Veuillez saisir un email valide' };
    }
    if (message.length < 10) {
        return { success: false, message: 'Le message doit contenir au moins 10 caractères' };
    }
    try {
        await ensureCsrfContact();
        const fd = new FormData();
        fd.append('name', name);
        fd.append('email', email);
        fd.append('subject', subject);
        fd.append('message', message);
        // Consent (même si backend ne le lit pas encore : prêt pour extension RGPD)
        fd.append('consent', consent ? '1' : '0');
        const data = await ecoApi.post('/contact', fd, { headers:{} }); // ecoApi préfixe déjà /EcoRide/api
        if (!data || !data.success) {
            return { success: false, message: (data && data.message) ? data.message : 'Erreur envoi.' };
        }
        return { success: true, email, message: data.message || 'Message envoyé' };
    } catch(err) {
        console.error('Contact form error:', err);
        const backendMsg = (err && err.message) ? err.message : null;
        return { success: false, message: backendMsg || 'Erreur réseau.' };
    }
}
