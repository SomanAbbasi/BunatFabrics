/**
 * Bunat Fabrics — Frontend enhancements:
 *  1. Show / hide password toggles.
 *  2. Email validation on submit.
 *  3. Profile menu dropdown.
 *  4. Wholesale Add-to-Cart Box & Slide-Over Drawer.
 *  5. Wholesale bundle presets.
 */
(function () {
    'use strict';

    var EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) {
            return meta.content;
        }
        var input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    function getBaseUrl() {
        var meta = document.querySelector('meta[name="app-url"]');
        if (meta && meta.content) {
            return meta.content.replace(/\/+$/, '');
        }
        return '';
    }

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

    /* Wholesale Cart Drawer Controller ------------------------------------- */

    var drawerOverlay = document.getElementById('cart-drawer-overlay');
    var drawer = document.getElementById('cart-drawer');
    var drawerItemsContainer = document.getElementById('cart-drawer-items');
    var drawerEmptyState = document.getElementById('cart-drawer-empty');
    var drawerFooter = document.getElementById('cart-drawer-footer');
    var drawerSubtotal = document.getElementById('cart-drawer-subtotal');
    var drawerBadge = document.getElementById('cart-drawer-count-badge');
    var drawerAlert = document.getElementById('cart-drawer-alert');
    var drawerAlertText = document.getElementById('cart-drawer-alert-text');
    var alertTimer = null;

    function updateHeaderCartCount(count) {
        var badge = document.getElementById('header-cart-count');
        var navCart = document.querySelector('.nav-cart');

        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? '' : 'none';
        }

        if (navCart) {
            if (count > 0) {
                navCart.classList.add('has-items');
            } else {
                navCart.classList.remove('has-items');
            }
        }
    }

    function renderCartDrawer(cart) {
        if (!cart) return;

        var count = cart.count || 0;
        updateHeaderCartCount(count);

        if (drawerBadge) {
            drawerBadge.textContent = count;
        }

        if (drawerSubtotal && cart.subtotal_formatted) {
            drawerSubtotal.textContent = cart.subtotal_formatted;
        }

        if (!cart.items || cart.items.length === 0) {
            if (drawerEmptyState) drawerEmptyState.removeAttribute('hidden');
            if (drawerFooter) drawerFooter.setAttribute('hidden', '');
            if (drawerItemsContainer) drawerItemsContainer.innerHTML = '';
            return;
        }

        if (drawerEmptyState) drawerEmptyState.setAttribute('hidden', '');
        if (drawerFooter) drawerFooter.removeAttribute('hidden');

        if (!drawerItemsContainer) return;

        var html = '';
        var baseUrl = getBaseUrl();

        cart.items.forEach(function (item) {
            var imgHtml = item.image_url
                ? '<img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title) + '">'
                : '<span class="product-placeholder" aria-hidden="true">B</span>';

            var itemUrl = baseUrl + '/product?id=' + encodeURIComponent(item.id);

            html += '<article class="cart-drawer-item" data-cart-item="' + item.id + '">'
                + '<a class="drawer-item-img" href="' + itemUrl + '">' + imgHtml + '</a>'
                + '<div class="drawer-item-info">'
                + '  <span class="drawer-item-code">' + escapeHtml(item.product_code || '') + '</span>'
                + '  <h3 class="drawer-item-title"><a href="' + itemUrl + '">' + escapeHtml(item.title) + '</a></h3>'
                + '  <div class="drawer-item-price">' + escapeHtml(item.price_formatted) + ' / ' + escapeHtml(item.unit) + '</div>'
                + '  <div class="drawer-item-controls">'
                + '    <div class="drawer-qty-stepper">'
                + '      <button type="button" class="drawer-stepper-btn" data-drawer-step="-1" data-product-id="' + item.id + '" aria-label="Decrease">&minus;</button>'
                + '      <input type="number" class="drawer-qty-input" data-product-id="' + item.id + '" value="' + item.quantity + '" min="1" max="1000000">'
                + '      <button type="button" class="drawer-stepper-btn" data-drawer-step="1" data-product-id="' + item.id + '" aria-label="Increase">&plus;</button>'
                + '    </div>'
                + '    <span class="drawer-unit-label">' + escapeHtml(item.unit) + 's</span>'
                + '  </div>'
                + '</div>'
                + '<div class="drawer-item-side">'
                + '  <div class="drawer-item-total">' + escapeHtml(item.line_total_formatted) + '</div>'
                + '  <button type="button" class="drawer-remove-btn" data-drawer-remove="' + item.id + '" title="Remove fabric">'
                + '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>'
                + '  </button>'
                + '</div>'
                + '</article>';
        });

        drawerItemsContainer.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function openCartDrawer(message) {
        if (!drawer || !drawerOverlay) return;

        drawerOverlay.removeAttribute('hidden');
        drawer.removeAttribute('hidden');

        // Force reflow for smooth animation
        void drawer.offsetWidth;

        drawerOverlay.classList.add('is-open');
        drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (message && drawerAlert && drawerAlertText) {
            drawerAlertText.textContent = message;
            drawerAlert.removeAttribute('hidden');

            clearTimeout(alertTimer);
            alertTimer = setTimeout(function () {
                drawerAlert.setAttribute('hidden', '');
            }, 3500);
        } else if (drawerAlert) {
            drawerAlert.setAttribute('hidden', '');
        }
    }

    function closeCartDrawer() {
        if (!drawer || !drawerOverlay) return;

        drawerOverlay.classList.remove('is-open');
        drawer.classList.remove('is-open');
        document.body.style.overflow = '';

        setTimeout(function () {
            drawerOverlay.setAttribute('hidden', '');
            drawer.setAttribute('hidden', '');
        }, 300);
    }

    function refreshCartData() {
        var baseUrl = getBaseUrl();
        fetch(baseUrl + '/cart/data', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.cart) {
                renderCartDrawer(data.cart);
            }
        })
        .catch(function (err) {
            console.error('Error fetching cart:', err);
        });
    }

    // Intercept click on Cart link in header to open drawer
    document.addEventListener('click', function (event) {
        var cartTrigger = event.target.closest('[data-open-cart-drawer]');
        if (cartTrigger) {
            event.preventDefault();
            refreshCartData();
            openCartDrawer(null);
            return;
        }

        var closeBtn = event.target.closest('#cart-drawer-close, .close-drawer-action, #cart-drawer-overlay');
        if (closeBtn) {
            event.preventDefault();
            closeCartDrawer();
            return;
        }

        // Wholesale bundle preset click (product page)
        var presetBtn = event.target.closest('[data-set-qty]');
        if (presetBtn) {
            event.preventDefault();
            var qty = parseInt(presetBtn.getAttribute('data-set-qty'), 10);
            var form = presetBtn.closest('form') || document.querySelector('.add-to-cart');
            if (form) {
                var qtyInput = form.querySelector('input[name="quantity"]');
                if (qtyInput) {
                    qtyInput.value = qty;
                }
                form.querySelectorAll('[data-set-qty]').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn === presetBtn);
                });
            }
            return;
        }

        // Wholesale quick preset pill click (homepage card)
        var cardPreset = event.target.closest('[data-set-card-qty]');
        if (cardPreset) {
            event.preventDefault();
            var cardQty = parseInt(cardPreset.getAttribute('data-set-card-qty'), 10);
            var cardForm = cardPreset.closest('form');
            if (cardForm) {
                var cardInput = cardForm.querySelector('input[name="quantity"]');
                if (cardInput) {
                    cardInput.value = cardQty;
                }
                cardForm.querySelectorAll('[data-set-card-qty]').forEach(function (pill) {
                    pill.classList.toggle('is-active', pill === cardPreset);
                });
            }
            return;
        }

        // Drawer stepper buttons (+ / -)
        var stepBtn = event.target.closest('[data-drawer-step]');
        if (stepBtn) {
            event.preventDefault();
            var delta = parseInt(stepBtn.getAttribute('data-drawer-step'), 10);
            var productId = stepBtn.getAttribute('data-product-id');
            var input = stepBtn.parentElement.querySelector('.drawer-qty-input');
            if (input && productId) {
                var currentVal = parseInt(input.value, 10) || 1;
                var newVal = Math.max(1, currentVal + delta);
                input.value = newVal;
                updateDrawerItemQty(productId, newVal);
            }
            return;
        }

        // Drawer remove button
        var removeBtn = event.target.closest('[data-drawer-remove]');
        if (removeBtn) {
            event.preventDefault();
            var prodId = removeBtn.getAttribute('data-drawer-remove');
            if (prodId) {
                removeDrawerItem(prodId);
            }
            return;
        }
    });

    // Quantity input change inside drawer
    document.addEventListener('change', function (event) {
        var input = event.target.closest('.drawer-qty-input');
        if (input) {
            var productId = input.getAttribute('data-product-id');
            var val = Math.max(1, parseInt(input.value, 10) || 1);
            input.value = val;
            updateDrawerItemQty(productId, val);
        }
    });

    function updateDrawerItemQty(productId, quantity) {
        var baseUrl = getBaseUrl();
        var formData = new FormData();
        formData.append('_token', getCsrfToken());
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch(baseUrl + '/cart/update', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.cart) {
                renderCartDrawer(data.cart);
            }
        })
        .catch(function (err) {
            console.error('Error updating cart line:', err);
        });
    }

    function removeDrawerItem(productId) {
        var baseUrl = getBaseUrl();
        var formData = new FormData();
        formData.append('_token', getCsrfToken());
        formData.append('product_id', productId);

        fetch(baseUrl + '/cart/remove', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data && data.cart) {
                renderCartDrawer(data.cart);
            }
        })
        .catch(function (err) {
            console.error('Error removing cart line:', err);
        });
    }

    /* Intercept Add to Cart form submissions ------------------------------- */

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
        var originalText = submitBtn ? submitBtn.innerHTML : '';
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
                submitBtn.innerHTML = originalText;
            }

            if (data && data.ok) {
                var productId = formData.get('product_id');
                var cardBtn = form.querySelector('.card-cart-button');
                if (cardBtn) {
                    cardBtn.classList.add('is-added');
                }

                if (data.cart) {
                    renderCartDrawer(data.cart);
                }

                openCartDrawer(data.message || 'Fabric added to your wholesale order!');
            } else {
                alert(data && data.message ? data.message : 'Could not add fabric to cart.');
            }
        })
        .catch(function (err) {
            console.error('Add to cart failed:', err);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '';
                submitBtn.innerHTML = originalText;
            }
            // Fallback: normal submit if fetch fails
            form.submit();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileMenus(null);
            closeCartDrawer();
        }
    });

    // Populate drawer on initial page load if items exist
    if (drawer) {
        refreshCartData();
    }
})();
