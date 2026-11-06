<?php

declare(strict_types=1);

use App\Cart\CartService;
use App\Core\Session;

$drawerCartService = new CartService();
$drawerItems = $drawerCartService->items();
$drawerSubtotal = $drawerCartService->subtotal();
$drawerCount = $drawerCartService->count();
?>
<div class="cart-drawer-overlay" id="cart-drawer-overlay" hidden></div>
<aside class="cart-drawer" id="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" hidden>
    <div class="cart-drawer-header">
        <div class="cart-drawer-title-wrap">
            <svg class="icon drawer-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2l2.4 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H5"/></svg>
            <h2 id="cart-drawer-title">Your Wholesale Cart</h2>
            <span class="cart-drawer-badge" id="cart-drawer-count-badge"><?= e((string) $drawerCount) ?></span>
        </div>
        <button type="button" class="cart-drawer-close" id="cart-drawer-close" aria-label="Close cart">&times;</button>
    </div>

    <div class="cart-drawer-alert" id="cart-drawer-alert" hidden>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        <span id="cart-drawer-alert-text">Item successfully added to your order!</span>
    </div>

    <div class="cart-drawer-body" id="cart-drawer-body">
        <div class="cart-drawer-empty" id="cart-drawer-empty" <?= $drawerCount > 0 ? 'hidden' : '' ?>>
            <div class="cart-drawer-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2l2.4 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H5"/></svg>
            </div>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Browse our wholesale collections and add fabric bundles for your apparel production.</p>
            <button type="button" class="button button-compact button-secondary close-drawer-action">Browse Fabrics</button>
        </div>

        <div class="cart-drawer-items" id="cart-drawer-items">
            <!-- Dynamically populated or updated via JS -->
        </div>
    </div>

    <div class="cart-drawer-footer" id="cart-drawer-footer" <?= $drawerCount === 0 ? 'hidden' : '' ?>>
        <div class="cart-drawer-summary-row">
            <span class="drawer-summary-label">Estimated Subtotal</span>
            <span class="drawer-summary-amount" id="cart-drawer-subtotal"><?= e(pkr($drawerSubtotal)) ?></span>
        </div>
        <p class="drawer-wholesale-note">Bulk wholesale orders are confirmed by our textile desk prior to dispatch.</p>

        <div class="cart-drawer-actions">
            <a class="button button-primary w-100" href="<?= e(url('/checkout')) ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>Proceed to Checkout</span>
            </a>
            <div class="cart-drawer-subactions">
                <a class="button button-ghost button-compact" href="<?= e(url('/cart')) ?>">View Full Cart</a>
                <button type="button" class="button button-ghost button-compact close-drawer-action">Continue Browsing</button>
            </div>
        </div>
    </div>
</aside>
