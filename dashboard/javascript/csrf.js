/**
 * Shared CSRF helper.
 *
 * Reads the CSRF token from the <meta name="csrf-token"> tag and exposes:
 *   - window.getCsrfToken()  – returns the token string
 *   - window.csrfFetch()     – drop-in for window.fetch() that always sends the
 *                              X-CSRF-Token request header on state-changing methods
 */
(function () {
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function csrfFetch(url, options) {
        options = options || {};
        const method = (options.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            options.headers = options.headers || {};
            options.headers['X-CSRF-Token'] = getCsrfToken();
        }
        return fetch(url, options);
    }

    window.getCsrfToken = getCsrfToken;
    window.csrfFetch = csrfFetch;
})();
