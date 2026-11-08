/**
 * Bunat Fabrics — Clean progressive enhancements:
 *  1. Password visibility toggle.
 *  2. Simple email validation on submit.
 *  3. Profile menu dropdown.
 *  4. Seamless add-to-cart without full page reload.
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
            var siteHeader = document.querySelector('.site-header');
            var navToggle = document.getElementById('nav-toggle');
            if (siteHeader && siteHeader.classList.contains('is-nav-open')) {
                siteHeader.classList.remove('is-nav-open');
                if (navToggle) {
                    navToggle.setAttribute('aria-expanded', 'false');
                }
            }
        }
    });

    /* Admin sidebar collapse toggle fallback ------------------------------- */

    document.addEventListener('click', function (event) {
        var toggler = event.target.closest('[data-bs-toggle="collapse"]');
        if (!toggler) {
            return;
        }

        var targetSelector = toggler.getAttribute('data-bs-target') || toggler.getAttribute('href');
        if (!targetSelector) {
            return;
        }

        var target = document.querySelector(targetSelector);
        if (!target) {
            return;
        }

        // Progressive fallback if bootstrap event didn't fire
        if (!window.bootstrap) {
            event.preventDefault();
            var isOpen = target.classList.toggle('show');
            toggler.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    });

    /* Mobile navigation toggle -------------------------------------------- */

    var navToggle = document.getElementById('nav-toggle');
    var siteHeader = document.querySelector('.site-header');

    if (navToggle && siteHeader) {
        navToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = siteHeader.classList.toggle('is-nav-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.site-header') && siteHeader.classList.contains('is-nav-open')) {
                siteHeader.classList.remove('is-nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* Seamless Add-to-Cart (Stays on page, updates header count) ----------- */

    function updateHeaderCartCount(count) {
        var badges = document.querySelectorAll('#header-cart-count, #mobile-cart-count');
        var navCarts = document.querySelectorAll('.nav-cart, .nav-cart-mobile');

        badges.forEach(function (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? '' : 'none';
        });

        navCarts.forEach(function (navCart) {
            if (count > 0) {
                navCart.classList.add('has-items');
            } else {
                navCart.classList.remove('has-items');
            }
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var action = form.getAttribute('action') || '';
        if (!action.includes('/cart/add')) {
            return;
        }

        event.preventDefault();

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
        }

        var formData = new FormData(form);

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
            }

            if (data && data.ok) {
                var totalCount = data.cart && typeof data.cart.count === 'number' ? data.cart.count : 0;
                updateHeaderCartCount(totalCount);

                if (submitBtn) {
                    submitBtn.classList.add('is-added');
                    var textSpan = submitBtn.querySelector('.btn-text') || submitBtn.querySelector('span');
                    if (textSpan) {
                        textSpan.textContent = 'Added ✓';
                        setTimeout(function () {
                            textSpan.textContent = 'In cart (' + totalCount + ')';
                        }, 2000);
                    }
                }
            } else {
                alert(data && data.message ? data.message : 'Could not add fabric to cart.');
                if (submitBtn) {
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        })
        .catch(function (err) {
            console.error('Add to cart failed:', err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.innerHTML = originalBtnHtml;
            }
            form.submit();
        });
    });
})();
