// Util de normalisation pour objets covoiturage (schema_version=1)
// But: fournir une structure cohérente au front quelle que soit la forme de la réponse backend.

export function formatTime(h) {
  try {
    if (!h) return '—';
    return new Date(`1970-01-01T${h}`).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}).replace(':','h');
  } catch(e){
    return h || '—';
  }
}

export function normalizeRide(raw) {
  if (!raw || typeof raw !== 'object') return {
    departure:'—', arrival:'—', date:new Date().toISOString().slice(0,10), driver:{ initials:'NA', pseudo:'Chauffeur', rating:0, isEcological:false },
    vehicle:{ brand:'—', model:'—', color:'—', energy:'—', firstRegistrationDate:null }, preferences:[], departureTime:'—', arrivalTime:'À définir', duration:'À définir', price:0, availableSeats:0, reviews:[]
  };
  const driverObj = raw.driver || {
    initials: (raw.prenom && raw.nom) ? (raw.prenom[0] + raw.nom[0]).toUpperCase() : 'NA',
    pseudo: raw.prenom ? (raw.prenom + '_' + (raw.nom ? raw.nom[0] : '')).toLowerCase() : 'chauffeur',
    rating: raw.rating || 0,
    isEcological: raw.isEcological || raw.est_ecologique === 1
  };
  return {
    departure: raw.departure || raw.lieu_depart || '—',
    arrival: raw.arrival || raw.lieu_arrivee || '—',
    date: raw.date || raw.date_depart || new Date().toISOString().slice(0,10),
    driver: driverObj,
    vehicle: raw.vehicle || {
      brand: raw.vehicle?.brand || raw.marque || '—',
      model: raw.vehicle?.model || raw.modele || '—',
      color: raw.vehicle?.color || raw.couleur || '—',
      energy: raw.vehicle?.energy || raw.energie || '—',
      firstRegistrationDate: raw.vehicle?.firstRegistrationDate || raw.date_premiere_immatriculation || null
    },
    preferences: Array.isArray(raw.preferences) ? raw.preferences : [],
    departureTime: raw.departureTime || (raw.heure_depart ? formatTime(raw.heure_depart) : '—'),
    arrivalTime: raw.arrivalTime || (raw.heure_arrivee && raw.heure_arrivee !== '00:00:00' ? formatTime(raw.heure_arrivee) : 'À définir'),
    duration: raw.duration || 'À définir',
    price: raw.price || raw.prix_personne || 0,
    availableSeats: raw.availableSeats || raw.nb_places || 0,
    reviews: Array.isArray(raw.reviews) ? raw.reviews : []
  };
}
