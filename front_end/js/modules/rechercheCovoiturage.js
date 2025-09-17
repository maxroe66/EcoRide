/*
Module : rechercheCovoiturage.js
Rôle : logique métier pour la validation et la recherche de covoiturages.
Importé par la vue accueil.js.
*/

export function validateSearchFields(departure, arrival, date) {
    if (!departure || !arrival || !date) {
        return 'Veuillez remplir tous les champs';
    }
    if (departure === arrival) {
        return "La ville de départ et d'arrivée doivent être différentes";
    }
    return null;
}

export function redirectToResults(departure, arrival, date) {
    const params = new URLSearchParams({
        from: departure,
        to: arrival,
        date: date
    });
    window.location.href = 'covoiturage.html?' + params.toString();
}
