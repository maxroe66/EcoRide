
/*
Module : connexionModule.js
*/

import { showLoader, hideLoader, displayError } from '../utils/utils.js';
import { ecoApi } from '../api/ecoApi.js';

export async function ensureCsrf(){ if(ecoApi) await ecoApi.ensureCsrf(); }

export function validatePassword(password) {
    const minLength = password.length >= 8;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[@#$%^&*(),.?":{}|<>]/.test(password);
    return minLength && hasUpper && hasLower && hasNumber && hasSpecial;
}

export function showError(message) { displayError(message); }
export function showSuccess(message) { displayError(message, 'success'); }
export function hideMessages() { displayError('', 'clear'); }

export function setupTabs() {
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
            hideMessages();
        });
    });
}

export function setupPasswordValidation() {
    document.getElementById('registerPassword').addEventListener('input', function(e) {
        const password = e.target.value;
        const requirements = document.querySelector('.password-requirements');
        if (password && !validatePassword(password)) {
            requirements.style.borderLeft = '4px solid #FF5722';
        } else if (password) {
            requirements.style.borderLeft = '4px solid #4A7C59';
        } else {
            requirements.style.borderLeft = 'none';
        }
    });
}

export function setupLoginForm() {
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        if (!email || !password) {
            displayError('Veuillez remplir tous les champs.');
            return;
        }
        try {
            showLoader(document.getElementById('loginForm').querySelector('button[type="submit"]'));
            await ensureCsrf();
            const data = new FormData(); // 'action' supprimé (non utilisé côté backend)
            data.append('email', email);
            data.append('password', password);
            const result = await ecoApi.post('/auth/login', data, { headers:{} });
            hideLoader(document.getElementById('loginForm').querySelector('button[type="submit"]'));
            if (result.success) {
                displayError('Connexion réussie ! Redirection en cours...', 'success');
                localStorage.setItem('userLoggedIn', 'true');
                setTimeout(() => {
                    window.location.href = 'accueil.html';
                }, 2000);
            } else {
                displayError(result.message || 'Erreur de connexion. Vérifiez vos identifiants.');
            }
        } catch (error) {
            hideLoader(document.getElementById('loginForm').querySelector('button[type="submit"]'));
            displayError('Erreur lors de la tentative de connexion.');
            console.error(error);
        }
    });
}

export function setupRegisterForm() {
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const nom = document.getElementById('registerNom').value;
        const prenom = document.getElementById('registerPrenom').value;
        const pseudo = document.getElementById('registerPseudo').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        if (!nom || !prenom || !pseudo || !email || !password || !confirmPassword) {
            displayError('Veuillez remplir tous les champs.');
            return;
        }
        const pseudoRegex = /^[\p{L}0-9._-]{3,50}$/u;
        if (!pseudoRegex.test(pseudo)) {
            displayError('Pseudo invalide (3-50 caractères, lettres, chiffres, ., -, _ sans espace).');
            return;
        }
        if (password !== confirmPassword) {
            displayError('Les mots de passe ne correspondent pas.');
            return;
        }
        if (!validatePassword(password)) {
            displayError('Le mot de passe ne respecte pas les critères de sécurité.');
            return;
        }
        try {
            showLoader(document.getElementById('registerForm').querySelector('button[type="submit"]'));
            await ensureCsrf();
            const data = new FormData(); // 'action' supprimé (non utilisé côté backend)
            data.append('nom', nom);
            data.append('prenom', prenom);
            data.append('email', email);
            data.append('pseudo', pseudo);
            data.append('password', password);
            data.append('telephone','');
            const result = await ecoApi.post('/auth/register', data, { headers:{} });
            hideLoader(document.getElementById('registerForm').querySelector('button[type="submit"]'));
            if (result.success) {
                displayError('Compte créé avec succès ! 20 crédits ajoutés. Vous pouvez maintenant vous connecter.', 'success');
                document.getElementById('registerForm').reset();
                setTimeout(() => {
                    document.querySelector('[data-tab="login"]').click();
                }, 2000);
            } else {
                displayError(result.message || 'Erreur lors de l\'inscription.');
            }
        } catch (error) {
            hideLoader(document.getElementById('registerForm').querySelector('button[type="submit"]'));
            displayError('Erreur lors de la tentative d\'inscription.');
            console.error(error);
        }
    });
}
