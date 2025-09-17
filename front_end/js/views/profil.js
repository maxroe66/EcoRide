/*
Vue : profil.js
Rôle : Orchestration de la page profil utilisateur. Importe le module métier et gère l’affichage, les interactions et les appels API.
*/



import {
    chargerProfil,
    demarrerCovoiturage,
    terminerCovoiturage,
    confirmerTrajet,
    annulerCovoiturage,
    chargerHistoriqueCovoiturages
} from '../modules/profilModule.js';
import { ensureCsrf } from '../api/ecoApi.js';

export async function initProfil() {
    await ensureCsrf();
    // Chargement du profil (module gère ecoApi et UI)
    chargerProfil();
    // Ajout des event listeners JS modernes sur les boutons d'action de l'historique
    const hist = document.getElementById('historiqueContent');
    if (!hist) return;
    hist.addEventListener('click', async function(e) {
        const btn = e.target.closest('button');
        if (!btn || btn.disabled) return;
        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id');
        const type = btn.getAttribute('data-type');
        btn.disabled = true;
        try {
            if (action === 'demarrer') {
                await demarrerCovoiturage(id);
            } else if (action === 'terminer') {
                await terminerCovoiturage(id);
            } else if (action === 'annuler') {
                await annulerCovoiturage(type, id);
            } else if (action === 'confirmer') {
                await confirmerTrajet(id);
            }
        } finally {
            // Réactive le bouton pour éviter un blocage si l'utilisateur veut réessayer
            btn.disabled = false;
        }
    });
}

