/**
 * Configuración global de Axios para peticiones AJAX autenticadas con cookies
 * y protección CSRF compatible con Laravel.
 */

import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

/**
 * Actualiza el meta tag csrf-token del documento con el token recibido del servidor.
 * @param {string} token - Token CSRF vigente.
 */
export function syncCsrfToken(token) {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    if (meta && token) {
        meta.content = token;
    }
}

// Sincroniza el token CSRF inicial presente en el HTML al cargar la página.
const initialMeta = document.head.querySelector('meta[name="csrf-token"]');
if (initialMeta?.content) {
    syncCsrfToken(initialMeta.content);
}

