
/*
Module : vehiculeModule.js
Rôle : logique métier pour le chargement et l’affichage des véhicules dans le select.
Importé par les vues qui nécessitent la gestion des véhicules.
*/

import { ecoApi } from '../api/ecoApi.js';

export async function chargerVehicules(selectVehiculeId) {
    const selectVehicule = document.getElementById(selectVehiculeId);
    // Fonction locale pour afficher les erreurs
    const displayError = (msg) => {
        const el = document.getElementById('ajoutCovoiturageMessage');
        if (el) {
            el.style.color = 'red';
            el.textContent = msg;
        }
    };
    try {
        const vehiculesData = await ecoApi.get('/vehicules', { credentials: 'include' });
        if (vehiculesData.success && vehiculesData.vehicules.length > 0) {
            selectVehicule.innerHTML = '<option value="">Sélectionner un véhicule...</option>';
            vehiculesData.vehicules.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.voiture_id;
                opt.textContent = `${v.marque || 'Marque'} ${v.modele} (${v.couleur}) - ${v.immatriculation}`;
                selectVehicule.appendChild(opt);
            });
        } else {
            selectVehicule.innerHTML = '<option value="">Aucun véhicule disponible</option>';
            displayError('Vous devez d\'abord ajouter un véhicule pour créer un covoiturage.');
        }
    } catch (error) {
        selectVehicule.innerHTML = '<option value="">Erreur de chargement</option>';
        displayError('Erreur lors du chargement des véhicules.');
        console.error('Erreur:', error);
    }
}
