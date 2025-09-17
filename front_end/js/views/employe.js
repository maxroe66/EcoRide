/*
Vue : employe.js
Rôle : Orchestration de la page espace employé.
Ce fichier “view” initialise la page, gère les événements et délègue la logique métier au module employeModule.js.
*/


import {
    checkEmployeSession,
    loadAvisEnAttente,
    validerOuRefuserAvis,
    loadIncidents,
    resolveIncident,
    loadLitigesHistorique
} from '../modules/employeModule.js';

export async function initEmploye() {
    await checkEmployeSession();
    await loadAvisEnAttente();
    await loadLitigesHistorique();
    setTimeout(loadIncidents, 300);
    // Migration bouton retour : event listener JS moderne
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            history.back();
        });
    }
}
