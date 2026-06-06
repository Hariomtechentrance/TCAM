/**
 * TCAM Backend URL Configuration
 *
 * For local development: leave TCAM_BACKEND_URL as '' (same-origin).
 * For Render split deployment: set TCAM_BACKEND_URL to your backend service URL.
 *
 * The Render frontend build command overwrites this file:
 *   echo "window.TCAM_BACKEND_URL='${BACKEND_URL}';" > js/config.js
 */
window.TCAM_BACKEND_URL = '';

/**
 * Returns the full URL for a backend endpoint path.
 * Usage: fetch(tcamApi('save-booking.php'), { method: 'POST', ... })
 */
window.tcamApi = function (path) {
    var base = (window.TCAM_BACKEND_URL || '').replace(/\/$/, '');
    return base + '/' + path.replace(/^\//, '');
};

/**
 * After the DOM is ready, rewrite any <a href="*.php"> links to point to the backend.
 * This makes nav and footer PHP links work when the frontend is deployed as a static site.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.TCAM_BACKEND_URL) return;
    var base = window.TCAM_BACKEND_URL.replace(/\/$/, '');
    document.querySelectorAll('a[href]').forEach(function (a) {
        var href = a.getAttribute('href');
        if (
            href &&
            /\.php(\?|#|$)/.test(href) &&
            !href.startsWith('http') &&
            !href.startsWith('//') &&
            !href.startsWith('#')
        ) {
            a.setAttribute('href', base + '/' + href.replace(/^\//, ''));
        }
    });
});
