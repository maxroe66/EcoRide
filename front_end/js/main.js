// Point d'entrée front-end EcoRide
// - Route vers les vues en fonction de <body data-page="...">
// - Initialise des features globales si nécessaire

// Imports globaux (header, etc.)
import { initHeader, initResponsiveHeader, checkUserSessionAndShowBanner } from './modules/headerModule.js';

const routes = {
  // Associe une valeur de data-page à un import dynamique d'une vue
  // Exemple: <body data-page="home">
  accueil: () => import('./views/accueil.js').then(m => m.initAccueil?.()),
  home: () => import('./views/home.js').then(m => m.initHome?.()),
  covoiturage: () => import('./views/covoiturage.js').then(m => m.initCovoiturage?.()),
  'covoiturage-detail': () => import('./views/covoiturage-detail.js').then(m => m.initCovoiturageDetail?.()),
  admin: () => import('./views/admin.js').then(m => m.initAdmin?.()),
  profil: () => import('./views/profil.js').then(m => m.initProfil?.()),
  connexion: () => import('./views/connexion.js').then(m => m.initConnexion?.()),
  contact: () => import('./views/contact.js').then(m => m.initContact?.()),
  'ajouter-covoiturage': () => import('./views/ajouter-covoiturage.js').then(m => m.initAjouterCovoiturage?.()),
  employe: () => import('./views/employe.js').then(m => m.initEmploye?.()),
  'espace-utilisateur': () => import('./views/espace-utilisateur.js').then(m => m.initEspaceUtilisateur?.()),
  // ajoutez ici d'autres pages au besoin
};

async function initGlobal() {
  // Initialisations globales (header, responsive, session/banner)
  try {
    await initHeader();
  } catch (e) {
    console.error('[main] initHeader failed:', e);
  }
  try {
    initResponsiveHeader();
  } catch (e) {
    console.error('[main] initResponsiveHeader failed:', e);
  }
  try {
    await checkUserSessionAndShowBanner();
  } catch (e) {
    console.error('[main] checkUserSessionAndShowBanner failed:', e);
  }
}

function initPage() {
  const body = document.body;
  const page = body?.dataset?.page;
  if (!page) return;
  const runner = routes[page];
  if (typeof runner === 'function') {
    runner().catch(err => {
      console.error(`[main] Erreur d'initialisation pour la page '${page}':`, err);
    });
  } else {
    console.warn(`[main] Aucun routeur trouvé pour data-page='${page}'`);
  }
}

window.addEventListener('DOMContentLoaded', async () => {
  await initGlobal();
  initPage();
});
