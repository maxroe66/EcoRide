
import { ecoApi } from '../api/ecoApi.js';

// ====== Injection du header si absent ======

function injectHeaderIfMissing() {
    if (!document.querySelector('header')) {
        const headerHTML = `
            <header>
                <nav>
                    <a href="accueil.html" class="logo">
                        <img src="../icons_images/ecorideicon-removebg-preview.png" alt="Ecoride Icon">
                    </a>
                    <ul class="nav-links">
                        <li><a href="accueil.html">Accueil</a></li>
                        <li><a href="covoiturage.html">Covoiturage</a></li>
                        <li><a href="connexion.html">Connexion</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </nav>
            </header>
        `;
        const container = document.querySelector('.page-container') || document.body;
        container.insertAdjacentHTML('afterbegin', headerHTML);
        // Ajout dynamique de la classe 'active' sur le lien correspondant à la page courante
        updateActiveLink(container);
    } else {
        // Si le header existe déjà, on met à jour la classe active
        updateActiveLink(document);
    }
}

function updateActiveLink(root) {
    const navLinks = root.querySelectorAll('.nav-links a');
    const currentPage = location.pathname.split('/').pop();
    navLinks.forEach(link => {
        const isActive = link.getAttribute('href') === currentPage;
        link.classList.toggle('active', isActive);
        if (isActive) link.setAttribute('aria-current','page'); else link.removeAttribute('aria-current');
    });
}

// ====== Header dynamique (session) ======

export async function initHeader() {
    injectHeaderIfMissing();
    const nav = document.querySelector('ul.nav-links');
    if (!nav) return;

    function addAdminLink() {
        if (nav.querySelector('li[data-admin]')) return;
        const li = document.createElement('li');
        li.setAttribute('data-admin','true');
        li.innerHTML = '<a href="admin.html">Espace admin</a>';
        nav.appendChild(li);
    }
    function addEmployeLink() {
        if (nav.querySelector('li[data-employe]')) return;
        const li = document.createElement('li');
        li.setAttribute('data-employe','true');
        li.innerHTML = '<a href="employe.html">Espace employé</a>';
        nav.appendChild(li);
    }
    async function checkSession() {
        try {
            const json = await ecoApi.get('/session');
            if (json.connected) {
                if (json.type_utilisateur === 'administrateur') addAdminLink();
                if (json.type_utilisateur === 'employe') addEmployeLink();
                ensureProfileLink();
                removeConnexionLink();
            }
            return !!json.connected;
        } catch(_) { return false; }
    }
    async function logout() {
        try { await ecoApi.post('/auth/logout', {}); } catch(_) {}
        try { localStorage.removeItem('userLoggedIn'); } catch(_) {}
        window.location.href = 'accueil.html';
    }
    function renderLogin() {
        let li = nav.querySelector('li[data-auth]');
        if (!li) {
            const loginA = nav.querySelector('a[href$="connexion.html"]');
            li = loginA ? loginA.closest('li') : document.createElement('li');
            if (!loginA) nav.appendChild(li);
        }
        li.setAttribute('data-auth','login');
        const isConnexionPage = /connexion\.html(?:$|[?#])/i.test(location.pathname);
        li.innerHTML = `<a href="connexion.html"${isConnexionPage ? ' class="active"':''}>Connexion</a>`;
    }
    function renderLogout() {
        let li = nav.querySelector('li[data-auth]');
        if (!li) {
            li = document.createElement('li');
            nav.appendChild(li);
        }
        li.setAttribute('data-auth','logout');
        li.innerHTML = '<a href="#" id="navLogoutLink">Déconnexion</a>';
        const link = li.querySelector('#navLogoutLink');
        if (link) link.addEventListener('click', e => { e.preventDefault(); logout(); });
    }
    function ensureProfileLink() {
        if (!nav.querySelector('li[data-profil]')) {
            const li = document.createElement('li');
            li.setAttribute('data-profil','true');
            li.innerHTML = '<a href="profil.html">Profil</a>';
            const authLi = nav.querySelector('li[data-auth]');
            if (authLi) nav.insertBefore(li, authLi); else nav.appendChild(li);
        }
    }
    function removeConnexionLink() {
        // Supprime tous les <li> contenant un lien vers connexion.html
        nav.querySelectorAll('a[href$="connexion.html"]').forEach(a => {
            const li = a.closest('li');
            if (li) li.remove();
        });
        // Supprime aussi le <li> dynamique si présent
        const authLi = nav.querySelector('li[data-auth="login"]');
        if (authLi) authLi.remove();
    }
    const connected = await checkSession();
    connected ? renderLogout() : renderLogin();
}

export function initResponsiveHeader() {
    injectHeaderIfMissing();
    const nav = document.querySelector('nav');
    if(!nav) return;
    let links = nav.querySelector('.nav-links');
    if(!links) return;
    let toggle = nav.querySelector('.nav-toggle');
    if(!toggle){
        toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'nav-toggle';
        toggle.setAttribute('aria-expanded','false');
        toggle.setAttribute('aria-label','Ouvrir le menu');
        toggle.title = 'Menu';
        toggle.innerHTML = '<span class="sr-only">Menu</span><span class="bar"></span><span class="bar"></span><span class="bar"></span>';
        nav.insertBefore(toggle, links);
    }
    if (!links.id) links.id = 'primary-navigation';
    toggle.setAttribute('aria-controls', links.id);
    links.setAttribute('aria-hidden','true');
    document.documentElement.classList.add('has-js');
    function syncState(initial){
        const isDesktop = window.matchMedia('(min-width: 769px)').matches;
        if(isDesktop){
            links.classList.add('open');
            links.style.display = 'flex';
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded','false');
            links.setAttribute('aria-hidden','false');
        } else {
            if(!toggle.classList.contains('active')){
                links.classList.remove('open');
                links.style.display = 'none';
                links.setAttribute('aria-hidden','true');
            }
            if(initial){
                toggle.classList.remove('active');
                toggle.setAttribute('aria-expanded','false');
                links.setAttribute('aria-hidden','true');
            }
        }
    }
    function closeOnEscape(e){ if(e.key === 'Escape' && toggle.classList.contains('active')) setMenuOpen(false); }
    function setMenuOpen(open){
        links.classList.toggle('open', open);
        links.style.display = open ? 'flex' : 'none';
        toggle.classList.toggle('active', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        links.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (!open) toggle.focus();
    }
    toggle.addEventListener('click', () => setMenuOpen(!links.classList.contains('open')));
    window.addEventListener('resize', () => {
        clearTimeout(toggle._rz);
        toggle._rz = setTimeout(() => syncState(false), 120);
    });
    document.addEventListener('keydown', closeOnEscape);
    syncState(true);
}

export async function checkUserSessionAndShowBanner() {
    try {
        const sessionData = await ecoApi.get('/session');
        if (sessionData.connected) {
            // On récupère le crédit à jour via l’API dédiée
            let credit = sessionData.credit;
            try {
                const creditData = await ecoApi.get('/utilisateur/credit');
                if (creditData.success && typeof creditData.credit !== 'undefined') {
                    credit = creditData.credit;
                }
            } catch (_) { /* silencieux */ }
            showUserInfoBanner({ ...sessionData, credit });
        } else {
            localStorage.removeItem('userLoggedIn');
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de session :', error);
    }
}

export function showUserInfoBanner(userData) {
    const header = document.querySelector('header');
    if (!header) return;
    let info = document.getElementById('userInfo');
    if (!info) {
        info = document.createElement('div');
        info.id = 'userInfo';
        info.className = 'user-info-banner';
        const nav = header.querySelector('nav');
        if (nav && nav.nextSibling) header.insertBefore(info, nav.nextSibling); else header.appendChild(info);
    }
    info.textContent = `Bienvenue ${userData.nom} ${userData.prenom} | Crédit: ${userData.credit}€`;
}

// Injection automatique au chargement du module
injectHeaderIfMissing();
