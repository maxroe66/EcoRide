// Fonction utilitaire locale pour afficher les erreurs dans l'UI admin
function displayError(msg) {
    let el = document.getElementById('adminErrorMsg');
    if (!el) {
        el = document.createElement('div');
        el.id = 'adminErrorMsg';
        el.style.color = 'red';
        el.style.margin = '10px 0';
        const container = document.querySelector('.admin-container');
        if (container) container.insertBefore(el, container.firstChild);
    }
    el.textContent = msg;
}
/**
 * Récupère la valeur d’une variable CSS personnalisée.
 * @param {string} name Nom de la variable (ex: --color-chart-covoits)
 * @param {string} fallback Valeur de secours si non trouvée
 * @returns {string}
 */
function cssVar(name, fallback = '') {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name);
    return v ? v.trim() : fallback;
}
/**
 * Charge et affiche les statistiques utilisateurs pour l’admin.
 * Affiche le nombre total d’utilisateurs, d’employés, d’administrateurs, etc.
 */
export function afficherStatsUtilisateurs() {
    const container = document.getElementById('adminStatsContainer');
    if (!container) return;
    container.innerHTML = 'Chargement...';
    get('/admin/stats/utilisateurs')
        .then(data => {
            if (data.success) {
                container.classList.add('admin-stats-grid');
                container.innerHTML = `
                    <div class="admin-stat stat-bg-users">
                        <h4>Utilisateurs</h4>
                        <div class="value">${data.total || 0}</div>
                    </div>
                    <div class="admin-stat stat-bg-employes">
                        <h4>Employés</h4>
                        <div class="value">${data.employes || 0}</div>
                    </div>
                    <div class="admin-stat stat-bg-admins">
                        <h4>Admins</h4>
                        <div class="value">${data.admins || 0}</div>
                    </div>
                    <div class="admin-stat stat-bg-suspendus">
                        <h4>Suspendus</h4>
                        <div class="value">${data.suspendus || 0}</div>
                    </div>
                `;
            } else {
                container.innerHTML = '<p>Erreur chargement stats.</p>';
            }
        })
        .catch(() => { container.innerHTML = '<p>Erreur chargement stats.</p>'; });
}
/**
 * Initialise le token CSRF pour les opérations admin.
 * À appeler au chargement de la page admin.
 */
export async function loadCsrfAdmin() {
    await ensureCsrf();
}
/**
 * Charge et affiche la table des covoiturages pour l’admin.
 * Effets : innerHTML du container, binding des actions et export CSV.
 */
export function afficherCovoituragesAdmin() {
    const container = document.getElementById('adminCovoitsContainer');
    const searchInput = document.getElementById('adminCovoitSearch');
    const exportBtn = document.getElementById('exportCovoitsCsvBtn');
    if (!container) return;
    container.textContent = 'Chargement...';
    fetch('/EcoRide/api/admin/covoiturages', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (data.success && Array.isArray(data.covoiturages)) {
                let covoits = data.covoiturages;
                function renderTable(filter = '') {
                    let filtered = covoits;
                    if (filter) {
                        const f = filter.toLowerCase();
                        filtered = covoits.filter(c =>
                            (c.lieu_depart && c.lieu_depart.toLowerCase().includes(f)) ||
                            (c.lieu_arrivee && c.lieu_arrivee.toLowerCase().includes(f)) ||
                            (c.conducteur_nom && c.conducteur_nom.toLowerCase().includes(f)) ||
                            (c.conducteur_prenom && c.conducteur_prenom.toLowerCase().includes(f)) ||
                            (c.statut && c.statut.toLowerCase().includes(f))
                        );
                    }
                    let html = '<div class="admin-table-wrapper"><table class="admin-table" data-responsive="cards">';
                    html += '<tr><th>ID</th><th>Date</th><th>Heure</th><th>Départ</th><th>Arrivée</th><th>Conducteur</th><th>Places</th><th>Prix</th><th>Statut</th><th>Action</th></tr>';
                    filtered.forEach(c => {
                        html += `<tr>
                            <td data-label="ID">${c.covoiturage_id}</td>
                            <td data-label="Date">${c.date_depart}</td>
                            <td data-label="Heure">${c.heure_depart}</td>
                            <td data-label="Départ">${c.lieu_depart}</td>
                            <td data-label="Arrivée">${c.lieu_arrivee}</td>
                            <td data-label="Conducteur">${c.conducteur_nom} ${c.conducteur_prenom}</td>
                            <td data-label="Places">${c.nb_places}</td>
                            <td data-label="Prix">${c.prix_personne}€</td>
                            <td data-label="Statut" class="status-${c.statut}">${c.statut}</td>
                            <td data-label="Action">
                                <button class="btn-secondary" data-action="voir" data-id="${c.covoiturage_id}">Voir</button>
                            </td>
                        </tr>`;
                    });
                    html += '</table></div>';
                    container.innerHTML = html;
                    // Export CSV
                    if (exportBtn) {
                        exportBtn.onclick = function() {
                            let csv = 'ID,Date,Heure,Départ,Arrivée,Conducteur,Places,Prix,Statut\n';
                            filtered.forEach(c => {
                                csv += `${c.covoiturage_id},${c.date_depart},${c.heure_depart},"${c.lieu_depart}","${c.lieu_arrivee}","${c.conducteur_nom} ${c.conducteur_prenom}",${c.nb_places},${c.prix_personne},${c.statut}\n`;
                            });
                            const blob = new Blob([csv], { type: 'text/csv' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url; a.download = 'covoiturages.csv';
                            document.body.appendChild(a); a.click();
                            setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
                        };
                    }
                    // Boutons Voir => ouvre modale
                    container.querySelectorAll('button[data-action="voir"]').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            const modal = document.getElementById('covoitModal');
                            const modalBody = document.getElementById('covoitModalBody');
                            modal.classList.remove('is-hidden');
                            modalBody.innerHTML = 'Chargement...';
                            fetch('/EcoRide/api/covoiturages/details?id=' + id, { credentials: 'include' })
                                .then(r => r.json())
                                .then(res => {
                                    // Nouveau schéma: res.covoiturage (schema_version=1); ancien res.data supprimé
                                    if (res.success && (res.covoiturage || res.data)) {
                                        const raw = res.covoiturage || res.data; // compat éventuelle
                                        const d = normalizeRide(raw);
                                        modalBody.innerHTML = `
                                            <h3>Covoiturage #${d.id}</h3>
                                            <div class="modal-row"><span class="modal-label">Départ :</span> ${d.departure} (${d.date} à ${d.departureTime})</div>
                                            <div class="modal-row"><span class="modal-label">Arrivée :</span> ${d.arrival} (${d.arrivalTime})</div>
                                            <div class="modal-row"><span class="modal-label">Conducteur :</span> ${d.driver.pseudo} (${d.driver.rating}★)</div>
                                            <div class="modal-row"><span class="modal-label">Véhicule :</span> ${d.vehicle.brand} ${d.vehicle.model} (${d.vehicle.energy}, ${d.vehicle.color})</div>
                                            <div class="modal-row"><span class="modal-label">Prix :</span> ${d.price} €</div>
                                            <div class="modal-row"><span class="modal-label">Places dispo :</span> ${d.availableSeats}</div>
                                            <div class="modal-row"><span class="modal-label">Durée estimée :</span> ${d.duration}</div>
                                            <div class="modal-row"><span class="modal-label">Préférences :</span> <ul>${d.preferences.map(p => `<li>${p}</li>`).join('')}</ul></div>
                                        `;
                                    } else {
                                        modalBody.innerHTML = '<p>Erreur lors du chargement du détail.</p>';
                                    }
                                })
                                .catch(()=>{ modalBody.innerHTML = '<p>Erreur réseau.</p>'; });
                        });
                    });
                    // Fermeture modale
                    const modal = document.getElementById('covoitModal');
                    const closeBtn = document.getElementById('closeCovoitModal');
                    if (modal && closeBtn) {
                        closeBtn.onclick = () => { modal.classList.add('is-hidden'); };
                        modal.onclick = e => { if (e.target === modal) modal.classList.add('is-hidden'); };
                    }
                }
                renderTable();
                if (searchInput) { searchInput.oninput = e => renderTable(e.target.value); }
            } else {
                container.innerHTML = '<p>Aucun covoiturage trouvé.</p>';
            }
        })
        .catch(() => { container.innerHTML = '<p>Erreur chargement covoiturages.</p>'; });
}
/**
 * Passe les tables en mode cartes si largeur fenêtre petite et overflow détecté.
 * Ajoute data-responsive="cards" (CSS gère ensuite l'affichage).
 */
export function adaptTablesResponsively() {
    document.querySelectorAll('.admin-table-wrapper > table.admin-table').forEach(tbl => {
        const wrapper = tbl.parentElement;
        if (window.innerWidth < 560 && wrapper.scrollWidth - 4 > wrapper.clientWidth) {
            tbl.setAttribute('data-responsive', 'cards');
        }
    });
}
import { post, get, put, del, ensureCsrf } from '../api/ecoApi.js';
import { normalizeRide } from '../utils/normalizeRide.js';
/**
 * Affiche le graphique des covoiturages par jour (bar chart).
 * Filtre les dates futures et affiche un message si aucune donnée.
 */
export function afficherGraphiqueCovoiturages() {
    get('/admin/stats/covoiturages')
        .then(data => {
            if (!data.success) { console.warn('Erreur stats covoiturages', data); return; }
            const today = new Date().toISOString().slice(0,10);
            // On ne filtre plus les dates futures si aucune data sinon graphique vide en dev; on garde tout et on laissera l'axe gérer
            const filtered = data.stats.filter(item => item.date_depart);
            const labels = filtered.map(item => item.date_depart);
            const values = filtered.map(item => parseInt(item.nb_covoiturages || '0', 10));
            const ctxEl = document.getElementById('covoituragesChart');
            if (!ctxEl) return;
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js non encore chargé, retry dans 500ms');
                setTimeout(afficherGraphiqueCovoiturages, 500);
                return;
            }
            const ctx = ctxEl.getContext('2d');
            if (!labels.length) {
                ctxEl.parentElement.insertAdjacentHTML('beforeend', '<p class="chart-empty-msg">Aucune donnée disponible.</p>');
                return;
            }
            const covoitColor = cssVar('--color-chart-covoits','rgba(75, 192, 192, 0.5)');
            new Chart(ctx, {
                type: 'bar',
                data: { labels, datasets: [{ label: 'Covoiturages', data: values, backgroundColor: covoitColor }] },
                options: { responsive: true, scales: { y: { beginAtZero: true, precision:0 } } }
            });
        })
        .catch(e => console.error('Fetch stats covoiturages échec', e));
}

/**
 * Affiche le graphique des crédits gagnés par jour (bar chart) + total.
 * Filtre les dates futures et affiche un message si aucune donnée.
 */
export function afficherGraphiqueCredits() {
    get('/admin/stats/credits')
        .then(data => {
            if (!data.success) { console.warn('Erreur stats crédits', data); return; }
            const filtered2 = data.stats.filter(item => item.date_depart);
            const labels = filtered2.map(item => item.date_depart);
            const values = filtered2.map(item => parseInt(item.credits_gagnes || '0', 10));
            const ctxEl2 = document.getElementById('creditsChart');
            if (!ctxEl2) return;
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js non encore chargé (credits), retry dans 500ms');
                setTimeout(afficherGraphiqueCredits, 500);
                return;
            }
            const ctx2 = ctxEl2.getContext('2d');
            if (!labels.length) {
                ctxEl2.parentElement.insertAdjacentHTML('beforeend', '<p class="chart-empty-msg">Aucune donnée de crédits disponible.</p>');
            } else {
                const creditColor = cssVar('--color-chart-credits','rgba(255, 159, 64, 0.5)');
                new Chart(ctx2, {
                    type: 'bar',
                    data: { labels, datasets: [{ label: 'Crédits gagnés', data: values, backgroundColor: creditColor }] },
                    options: { responsive: true, scales: { y: { beginAtZero: true, precision:0 } } }
                });
            }
            const total = (data.total_credits !== undefined) ? data.total_credits : values.reduce((a,b)=>a+b,0);
            const totalEl = document.getElementById('totalCredits');
            if (totalEl) totalEl.textContent = 'Total des crédits gagnés : ' + total;
        })
        .catch(e => console.error('Fetch stats crédits échec', e));
}
/**
 * Gère la création d'un employé via POST JSON + reset + message + refresh liste.
 * Effet : message de statut, reset du formulaire, rafraîchissement de la table utilisateurs.
 */
export function setupFormCreerEmploye() {
    const formEmploye = document.getElementById('formCreerEmploye');
    if (formEmploye) {
        formEmploye.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formEmploye);
            const data = {}; formData.forEach((v, k) => data[k] = v);
            document.getElementById('employeMsg').textContent = 'Création en cours...';
            (async()=>{
                let res = await post('/admin/utilisateurs/employe', data);
                if (res.success){
                    document.getElementById('employeMsg').textContent = 'Employé créé avec succès !';
                    formEmploye.reset();
                    afficherUtilisateursAdmin();
                } else {
                    document.getElementById('employeMsg').textContent = res.message || 'Erreur lors de la création.';
                }
            })();
        });
    }
}
/**
 * Charge et affiche la table des utilisateurs :
 * - Filtrage par recherche
 * - Export CSV
 * - Suspension/réactivation
 * Effets : innerHTML du container, binding des actions et export.
 */
export function afficherUtilisateursAdmin() {
    get('/admin/utilisateurs')
        .then(data => {
            console.debug('[admin] Réponse /admin/utilisateurs', data);
            const container = document.getElementById('adminUsersContainer');
            if (!container) return;
            // Contrat stabilisé: on attend toujours data.success === true et data.utilisateurs (array)
            if (!(data && data.success && Array.isArray(data.utilisateurs))) {
                displayError('Erreur chargement utilisateurs : ' + (data && data.message ? data.message : 'Réponse invalide'));
                return;
            }

            let users = data.utilisateurs;
            const searchInput = document.getElementById('adminUserSearch');
            const exportBtn = document.getElementById('exportUsersCsvBtn');
            /** Rend la table utilisateurs filtrée. */
            function renderTable(filter = '') {
                let filtered = users;
                if (filter) {
                    const f = filter.toLowerCase();
                    filtered = users.filter(u =>
                        (u.nom && u.nom.toLowerCase().includes(f)) ||
                        (u.prenom && u.prenom.toLowerCase().includes(f)) ||
                        (u.email && u.email.toLowerCase().includes(f)) ||
                        (u.type_utilisateur && u.type_utilisateur.toLowerCase().includes(f))
                    );
                }
                let html = '<div class="admin-table-wrapper"><table class="admin-table" data-responsive="cards">';
                html += '<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Action</th></tr>';
                filtered.forEach(u => {
                    let actionBtn = '';
                    if (u.type_utilisateur !== 'administrateur') {
                        if (u.suspendu == 1) {
                            actionBtn = `<button class="btn-secondary" data-action="reactiver" data-id="${u.utilisateur_id}">Réactiver</button>`;
                        } else {
                            actionBtn = `<button class="btn-secondary" data-action="suspendre" data-id="${u.utilisateur_id}">Suspendre</button>`;
                        }
                    } else {
                        actionBtn = '-';
                    }
                    html += `<tr>
                        <td data-label="ID">${u.utilisateur_id}</td>
                        <td data-label="Nom">${u.nom}</td>
                        <td data-label="Prénom">${u.prenom}</td>
                        <td data-label="Email">${u.email}</td>
                        <td data-label="Rôle">${u.type_utilisateur}</td>
                        <td data-label="Statut">${u.suspendu == 1 ? '<span class="status-suspendu">Suspendu</span>' : '<span class="status-actif">Actif</span>'}</td>
                        <td data-label="Action">${actionBtn}</td>
                    </tr>`;
                });
                html += '</table></div>';
                container.innerHTML = html;
                // Binding actions
                container.querySelectorAll('button[data-action]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        const action = this.getAttribute('data-action');
                        suspendreOuReactivierUtilisateur(userId, action === 'suspendre' ? 1 : 0);
                    });
                });
                // Export CSV
                if (exportBtn) {
                    exportBtn.onclick = function() {
                        let csv = 'ID,Nom,Prénom,Email,Rôle,Statut\n';
                        filtered.forEach(u => {
                            csv += `${u.utilisateur_id},"${u.nom}","${u.prenom}","${u.email}",${u.type_utilisateur},${u.suspendu == 1 ? 'Suspendu' : 'Actif'}\n`;
                        });
                        const blob = new Blob([csv], { type: 'text/csv' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url; a.download = 'utilisateurs.csv';
                        document.body.appendChild(a); a.click();
                        setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(url); }, 100);
                    };
                }
            }
            renderTable();
            if (searchInput) { searchInput.oninput = e => renderTable(e.target.value); }
        })
        .catch(err => {
            console.error('Erreur fetch /admin/utilisateurs', err);
            displayError('Erreur réseau lors du chargement des utilisateurs.');
        });
}

/**
 * Suspends ou réactive un utilisateur via POST.
 * @param {number|string} utilisateur_id id utilisateur
 * @param {0|1} suspendre 1 = suspendre, 0 = réactiver
 */
export function suspendreOuReactivierUtilisateur(utilisateur_id, suspendre) {
    if (!confirm(suspendre ? 'Suspendre cet utilisateur ?' : 'Réactiver cet utilisateur ?')) {
        displayError('Action annulée par l\'administrateur.');
        return;
    }
    const payload = { utilisateur_id, suspendre };
    (async()=>{
        let data = await post('/admin/utilisateurs/suspendre', payload);
        if (data.success){
            afficherUtilisateursAdmin();
        } else {
            displayError(data.message || 'Action impossible.');
        }
    })();
}
