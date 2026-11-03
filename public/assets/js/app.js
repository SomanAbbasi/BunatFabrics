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

    /* Profile menu -------------------------------------------------------- */

    function closeProfileMenus(except) {
        document.querySelectorAll('[data-profile-toggle]').forEach(function (toggle) {
            if (toggle === except) {
                return;
            }

            var menu = document.getElementById(toggle.getAttribute('aria-controls'));

            if (menu) {
                menu.setAttribute('hidden', '');
            }

            toggle.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-profile-toggle]');

        if (toggle) {
            event.preventDefault();

            var menu = document.getElementById(toggle.getAttribute('aria-controls'));

            if (!menu) {
                return;
            }

            var open = menu.hasAttribute('hidden');

            closeProfileMenus(open ? toggle : null);

            if (open) {
                menu.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                menu.setAttribute('hidden', '');
                toggle.setAttribute('aria-expanded', 'false');
            }

            return;
        }

        if (!event.target.closest('.profile')) {
            closeProfileMenus(null);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileMenus(null);
        }
    });
})();
