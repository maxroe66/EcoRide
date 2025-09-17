<?php
// Tableau des routes de l'API. Chaque entrée indique :
// [ METHOD, PATH, CONTROLEUR, OPTIONNEL: REGLE ]
// REGLE peut être 'auth' (nécessite être connecté) ou 'role:xxx' (rôle spécifique)
// On commence simple avec quelques routes. On pourra en ajouter d'autres ensuite.

return [
    // Recherche de covoiturages (publique)
    ['GET', '/api/covoiturages', 'covoiturage/rechercher_covoiturages.php'],

    // Ping simple (diagnostic routeur)
    ['GET', '/api/ping', 'util/test_ping.php'],

    // Détails d'un covoiturage (publique)
    ['GET', '/api/covoiturages/details', 'covoiturage/get_covoiturage_details.php'],

    // Avis validés (publique)
    ['GET', '/api/avis/valides', 'avis/get_avis_valides.php'],

    // Avis chauffeur (publique)
    ['GET', '/api/avis/chauffeur', 'avis/get_chauffeur_avis.php'],

    // Jeton CSRF (publique)
    ['GET', '/api/csrf-token', 'security/get_csrf_token.php'],

    // Vérification session (publique - renvoie info si connecté)
    ['GET', '/api/session', 'auth/check_session.php'],

    // (Supprimé) Ancienne route multi-actions /api/traitement désormais dépréciée

    // Nouvelles routes auth dédiées (progressive migration hors traitement.php)
    ['POST', '/api/auth/register', 'auth/auth_register.php'],
    ['POST', '/api/auth/login', 'auth/auth_login.php'],
    ['POST', '/api/auth/logout', 'auth/auth_logout.php', 'auth'],

    // Route dédiée contact (remplace action contact_message de traitement.php)
    ['POST', '/api/contact', 'contact/contact_message.php'],

    // Participation à un covoiturage (remplace action participer de traitement.php)
    ['POST', '/api/covoiturages/participer', 'covoiturage/participer_covoiturage.php', 'auth'],

    // Ajouter un avis (MongoDB avec fallback)
    ['POST', '/api/avis', 'avis/ajouter_avis.php', 'auth'],

    // Ajouter un covoiturage (POST déjà présent plus haut) => conservé
    // Démarrer / Terminer / Annuler covoiturage
    ['POST', '/api/covoiturages/demarrer', 'covoiturage/demarrer_covoiturage.php', 'auth'],
    ['POST', '/api/covoiturages/terminer', 'covoiturage/terminer_covoiturage.php', 'auth'],
    ['POST', '/api/covoiturages/annuler', 'covoiturage/annuler_covoiturage.php', 'auth'],

    // Ajouter un covoiturage (utilisateur connecté chauffeur)
    ['POST', '/api/covoiturages', 'covoiturage/ajouter_covoiturage.php', 'auth'],

    // Valider / signaler une participation (connecté)
    ['POST', '/api/participations/valider', 'covoiturage/valider_participation.php', 'auth'],
    ['GET', '/api/utilisateur/historique', 'covoiturage/get_historique_covoiturages.php', 'auth'],

    // --- Utilisateur / Profil ---
    ['GET', '/api/utilisateur/profil', 'user/get_user_profile.php', 'auth'],
    ['GET', '/api/utilisateur/participations', 'user/get_user_participations.php', 'auth'],
    ['GET', '/api/utilisateur/credit', 'user/get_credit.php', 'auth'],
    ['POST', '/api/utilisateur/profil', 'user/update_profile.php', 'auth'], // (PUT serait plus REST, conservé POST MVP)

    // --- Véhicules ---
    ['GET', '/api/vehicules', 'vehicule/get_vehicules_utilisateur.php', 'auth'],
    ['GET', '/api/vehicules/profil', 'vehicule/get_vehicules_profil.php', 'auth'],
    ['POST', '/api/vehicules', 'vehicule/ajouter_vehicule.php', 'auth'],

    // --- Covoiturage complément ---
    ['POST', '/api/covoiturages/confirmer', 'covoiturage/confirmer_trajet.php', 'auth'],

    // --- Avis modération (admin + employé) ---
    ['GET', '/api/admin/avis/en-attente', 'avis/get_avis_en_attente.php', 'roles:administrateur,employe'],
    ['POST', '/api/admin/avis/valider', 'avis/valider_avis.php', 'roles:administrateur,employe'],

    // --- Incidents (admin + employé) ---
    ['GET', '/api/admin/incidents', 'incidents/get_incidents_en_cours.php', 'roles:administrateur,employe'],
    ['POST', '/api/admin/incidents/resoudre', 'incidents/resoudre_incident.php', 'roles:administrateur,employe'],
    ['GET', '/api/admin/incidents/tous', 'incidents/get_incidents_tous.php', 'roles:administrateur,employe'],
    
    // --- Administration utilisateurs ---
    ['GET', '/api/admin/utilisateurs', 'admin/lister_utilisateurs.php', 'role:administrateur'],
    ['POST', '/api/admin/utilisateurs/suspendre', 'admin/suspendre_utilisateur.php', 'role:administrateur'],
    ['POST', '/api/admin/utilisateurs/employe', 'admin/creer_employe.php', 'role:administrateur'],

    // --- Stats complémentaires ---
    ['GET', '/api/admin/stats/utilisateurs', 'stats/stats_utilisateurs.php', 'role:administrateur'],
    ['GET', '/api/admin/stats/credits', 'stats/stats_credits_plateforme.php', 'role:administrateur'],

    // --- Maintenance ---
    ['POST', '/api/admin/reset', 'admin/reset_data.php', 'role:administrateur'],

    // Lister covoiturages (admin)
    ['GET', '/api/admin/covoiturages', 'covoiturage/lister_covoiturages.php', 'role:administrateur'],

    // Statistiques covoiturages par jour (admin)
    ['GET', '/api/admin/stats/covoiturages', 'stats/stats_covoiturages_par_jour.php', 'role:administrateur'],
];
