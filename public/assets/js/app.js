/**
 * Bunat Fabrics — small progressive enhancements.
 *
 *  1. Show / hide password toggles.
 *  2. Simple client-side email validation on form submit.
 */
(function () {
    'use strict';

    var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /* Password visibility ------------------------------------------------- */

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

    /* Email validation ---------------------------------------------------- */

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var email = form.querySelector('input[type="email"]');

        if (!email) {
            return;
        }

        email.setCustomValidity('');

        if (!EMAIL_PATTERN.test(email.value.trim())) {
            email.setCustomValidity('Please enter a valid email address.');
            email.reportValidity();
            event.preventDefault();
        }
    });
})();
