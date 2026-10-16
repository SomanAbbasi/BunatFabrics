/**
 * Bunat Fabrics — small progressive enhancements.
 *
 * Show / hide password toggles. A button with
 * [data-toggle-password="<input-id>"] flips the target input between
 * password and text.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-toggle-password]');

        if (!toggle) {
            return;
        }

        event.preventDefault();

        var input = document.getElementById(toggle.getAttribute('data-toggle-password'));

        if (!input) {
            return;
        }

        var reveal = input.type === 'password';

        input.type = reveal ? 'text' : 'password';
        toggle.classList.toggle('is-visible', reveal);
        toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');

        input.focus();
    });
})();
