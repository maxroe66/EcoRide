/*
Module : ecoApi.js
Rôle : centralisation des appels API EcoRide (GET/POST/PUT/DELETE) avec gestion automatique du token CSRF.
Importable par tous les modules et vues.
*/

class EcoApi {
  constructor(base = '/EcoRide/api') {
    this.API_BASE = base;
    this.csrfToken = null;
    this.fetchingCsrf = null;
  }

  async ensureCsrf(force = false) {
    if (!force && this.csrfToken) return this.csrfToken;
    if (!this.fetchingCsrf) {
      this.fetchingCsrf = fetch(this.API_BASE + '/csrf-token', { credentials: 'include' })
        .then(r => r.json().catch(()=>({})))
        .then(j => {
          if (j && (j.csrf_token)) { this.csrfToken = j.csrf_token; }
          return this.csrfToken;
        })
        .catch(() => null)
        .finally(() => { this.fetchingCsrf = null; });
    }
    return this.fetchingCsrf;
  }

  buildHeaders(extra = {}, skipJsonContentType = false) {
    const h = Object.assign({ 'Accept': 'application/json' }, extra);
    if (!skipJsonContentType && !(h['Content-Type']) && !(h['content-type'])) {
      h['Content-Type'] = 'application/json';
    }
    return h;
  }

  async handleResponse(res) {
    let json = null;
    try { json = await res.json(); } catch (_) { }
    if (!res.ok) {
      const msg = (json && (json.message || json.error)) || ('HTTP ' + res.status);
      const err = new Error(msg); err.status = res.status; err.payload = json; throw err;
    }
    return json;
  }

  async request(method, path, body, opts = {}) {
    const url = this.API_BASE + path;
    const isForm = (typeof FormData !== 'undefined') && (body instanceof FormData);
    const init = { method, credentials: 'include', headers: this.buildHeaders(opts.headers || {}, isForm) };
    if (body !== undefined && body !== null) {
      if (isForm) {
        if (init.headers['Content-Type']) delete init.headers['Content-Type'];
        if (init.headers['content-type']) delete init.headers['content-type'];
        init.body = body;
      } else {
        init.body = (init.headers['Content-Type'] === 'application/json') ? JSON.stringify(body) : body;
      }
    }
    return fetch(url, init).then(res => this.handleResponse(res));
  }

  isCsrfError(err) {
    if (!err) return false;
    if (err.status === 403) {
      const msg = (err.message || '').toLowerCase();
      if (msg.includes('csrf') || msg.includes('token')) return true;
    }
    return false;
  }

  async mutating(method, path, data, opts = {}) {
    await this.ensureCsrf();
    if (data == null) data = {};
    const applyToken = () => {
      if (this.csrfToken) {
        if (typeof FormData !== 'undefined' && data instanceof FormData) {
          if (!data.has('csrf_token')) data.append('csrf_token', this.csrfToken);
          opts.headers = Object.assign({}, opts.headers || {});
          if (opts.headers['Content-Type']) delete opts.headers['Content-Type'];
        } else if (typeof data === 'object' && !Array.isArray(data)) {
          data.csrf_token = data.csrf_token || this.csrfToken;
        }
      }
    };
    applyToken();
    try {
      return await this.request(method, path, data, opts);
    } catch (err) {
      if (this.isCsrfError(err)) {
        // Retry unique: on force refresh du token
        await this.ensureCsrf(true);
        applyToken();
        return this.request(method, path, data, opts);
      }
      throw err;
    }
  }

  get(path) { return this.request('GET', path); }
  post(path, data, opts = {}) { return this.mutating('POST', path, data, opts); }
  put(path, data, opts = {}) { return this.mutating('PUT', path, data, opts); }
  del(path, data, opts = {}) { return this.mutating('DELETE', path, data, opts); }
  base() { return this.API_BASE; }
  csrf() { return this.csrfToken; }
  resetCsrf() { this.csrfToken = null; }
}


// Instanciation unique et exports ES6
const ecoApi = new EcoApi();
export const post = ecoApi.post.bind(ecoApi);
export const get = ecoApi.get.bind(ecoApi);
export const put = ecoApi.put.bind(ecoApi);
export const del = ecoApi.del.bind(ecoApi);
export const ensureCsrf = ecoApi.ensureCsrf.bind(ecoApi);


/**
 * Helper pour soumettre un formulaire via l’API (POST).
 * @param {string} path Chemin API
 * @param {object|FormData} data Données à envoyer
 * @returns {Promise<object>} Réponse API
 */
export async function ecoSubmit(path, data) {
  if (data instanceof FormData) {
    return ecoApi.request('POST', path, data);
  } else {
    return ecoApi.post(path, data);
  }
}

/**
 * Helper pour soumettre directement un formulaire DOM via l’API (POST).
 * @param {string} path Chemin API
 * @param {HTMLFormElement|FormData} form Formulaire ou FormData
 * @returns {Promise<object>} Réponse API
 */
export async function ecoSubmitForm(path, form) {
  const fd = (form instanceof FormData) ? form : new FormData(form);
  return ecoApi.request('POST', path, fd);
}


// Export nommé pour compatibilité ES6 modules
export { ecoApi };
