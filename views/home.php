<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}> $allGroups */
/** @var list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}> $groups */
/** @var int|null $activeCategory */
/** @var array<int, int> $cartQuantities */
$totalProducts = array_sum(array_map(static fn (array $group): int => count($group['products']), $allGroups));
?>
<section class="storefront">
    <!-- Professional B2B Wholesale Hero Section -->
    <header class="store-hero-pro">
        <div class="hero-pro-badge">B2B Textile Wholesale &amp; Converting</div>
        <h1>Direct Mill Fabric Sourcing For Apparel &amp; Fashion Brands</h1>
        <p class="hero-lead">
            High-density cotton, lawn, linen, twill, and blended textiles for clothing manufacturers. Order in full rolls, sample bolts, or custom bulk meterage with verified GSM and transparent wholesale PKR pricing.
        </p>

        <div class="hero-trust-pills">
            <div class="trust-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Direct Mill Wholesale Rates</span>
            </div>
            <div class="trust-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span>Fast Dispatch Across Pakistan</span>
            </div>
            <div class="trust-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                <span>Custom Bundle &amp; Roll Lengths</span>
            </div>
            <div class="trust-pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Verified GSM &amp; Colorfastness</span>
            </div>
        </div>
    </header>

    <!-- Value Pillars Bar -->
    <div class="wholesale-pillars">
        <div class="pillar-card">
            <div class="pillar-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            </div>
            <h3>Bulk Bundle Sourcing</h3>
            <p>Order 50m sample rolls to 10,000m+ full production containers with ease.</p>
        </div>
        <div class="pillar-card">
            <div class="pillar-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <h3>Lab-Verified Specs</h3>
            <p>Detailed fabric GSM, yarn counts, and width certified for apparel sewing.</p>
        </div>
        <div class="pillar-card">
            <div class="pillar-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3>Transparent PKR Rates</h3>
            <p>Wholesale prices with clear per-meter and per-yard tiering for brand budgeting.</p>
        </div>
        <div class="pillar-card">
            <div class="pillar-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <h3>Industrial Doorstep Cargo</h3>
            <p>Dispatched direct to garment factories in Karachi, Lahore, Faisalabad &amp; all cities.</p>
        </div>
    </div>

    <?php if ($unavailable): ?>
        <div class="empty-note" role="status">
            <p class="empty-note-title">Catalog temporarily unavailable</p>
            <p class="muted">Please try again in a moment.</p>
        </div>
    <?php elseif ($allGroups === []): ?>
        <div class="empty-note">
            <p class="empty-note-title">No fabrics available yet</p>
            <p class="muted">Our collections are being prepared. Please check back soon.</p>
        </div>
    <?php else: ?>
        <!-- Category Filter Tabs -->
        <nav class="category-nav" aria-label="Filter fabrics by category">
            <a class="<?= $activeCategory === null ? 'is-active' : '' ?>" href="<?= e(url('/')) ?>" <?= $activeCategory === null ? 'aria-current="page"' : '' ?>>
                All Fabrics
                <span><?= e((string) $totalProducts) ?></span>
            </a>
            <?php foreach ($allGroups as $group): ?>
                <a class="<?= $activeCategory === $group['id'] ? 'is-active' : '' ?>" href="<?= e(url('/?category=' . $group['id'])) ?>" <?= $activeCategory === $group['id'] ? 'aria-current="page"' : '' ?>>
                    <?= e($group['name']) ?>
                    <span><?= e((string) count($group['products'])) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($groups === []): ?>
            <div class="empty-note">
                <p class="empty-note-title">No fabrics in this collection yet</p>
                <p class="muted">Try another category or <a href="<?= e(url('/')) ?>">view all collections</a>.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($groups as $group): ?>
            <section class="category-section" id="category-<?= e((string) $group['id']) ?>" aria-labelledby="category-<?= e((string) $group['id']) ?>-title">
                <header class="section-heading">
                    <div>
                        <h2 id="category-<?= e((string) $group['id']) ?>-title"><?= e($group['name']) ?></h2>
                        <span class="text-secondary small">Wholesale collection &middot; <?= count($group['products']) ?> <?= count($group['products']) === 1 ? 'fabric' : 'fabrics' ?></span>
                    </div>
                    <span><?= e(ucfirst((string) ($group['products'][0]['unit'] ?? 'meter'))) ?> wholesale</span>
                </header>

                <div class="product-grid">
                    <?php foreach ($group['products'] as $product): ?>
                        <?php
                        $inCart = (int) ($cartQuantities[(int) $product['id']] ?? 0);
                        $unit = (string) ($product['unit'] ?? 'meter');
                        ?>
                        <article class="product-card">
                            <a class="product-card-link" href="<?= e(url('/product?id=' . $product['id'])) ?>">
                                <div class="product-media">
                                    <span class="media-tag-category"><?= e($group['name']) ?></span>
                                    <?php if ($product['weight_gsm'] !== null): ?>
                                        <span class="media-tag-specs"><?= e((string) $product['weight_gsm']) ?> GSM</span>
                                    <?php endif; ?>

                                    <?php if (! empty($product['image_path'])): ?>
                                        <img src="<?= e(ProductImageService::url((string) $product['image_path'])) ?>" alt="<?= e($product['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span class="product-placeholder" aria-hidden="true">B</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-body">
                                    <span class="product-code"><?= e($product['product_code']) ?></span>
                                    <h3><?= e($product['title']) ?></h3>
                                    <p class="product-price">
                                        <?= e(pkr($product['price_pkr'])) ?>
                                        <span class="unit-suffix" style="font-size: 0.82rem; font-weight: normal; color: var(--muted);">/ <?= e($unit) ?></span>
                                    </p>

                                    <dl class="product-meta">
                                        <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?>
                                            <div><dt>Fiber</dt><dd><?= e($product['composition']) ?></dd></div>
                                        <?php endif; ?>
                                        <?php if ($product['width_inch'] !== null): ?>
                                            <div><dt>Width</dt><dd><?= e((string) $product['width_inch']) ?> in</dd></div>
                                        <?php endif; ?>
                                        <div><dt>Sold By</dt><dd><?= e(ucfirst($unit)) ?>s</dd></div>
                                    </dl>
                                </div>
                            </a>

                            <!-- B2B Wholesale Quantity Order Form -->
                            <form action="<?= e(url('/cart/add')) ?>" method="POST" class="product-card-cart">
                                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                <input type="hidden" name="return_to" value="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">

                                <div class="card-wholesale-row">
                                    <div class="card-qty-wrapper">
                                        <label class="card-qty-label" for="card-qty-<?= e((string) $product['id']) ?>">Qty (<?= e($unit) ?>s)</label>
                                        <input type="number" id="card-qty-<?= e((string) $product['id']) ?>" name="quantity" value="50" min="1" max="1000000" class="card-qty-input" inputmode="numeric" required>
                                    </div>
                                    <button type="submit" class="card-cart-button<?= $inCart > 0 ? ' is-added' : '' ?>" title="<?= $inCart > 0 ? 'Add more to your order' : 'Add fabric to cart' ?>">
                                        <?= icon($inCart > 0 ? 'cart' : 'plus') ?>
                                        <span><?= $inCart > 0 ? 'In cart' : 'Add to cart' ?></span>
                                        <?php if ($inCart > 0): ?><span class="card-cart-count"><?= e((string) $inCart) ?></span><?php endif; ?>
                                    </button>
                                </div>

                                <div class="card-quick-presets">
                                    <button type="button" class="card-preset-pill" data-set-card-qty="10">+10</button>
                                    <button type="button" class="card-preset-pill is-active" data-set-card-qty="50">50<?= e($unit === 'meter' ? 'm' : 'yd') ?> (Roll)</button>
                                    <button type="button" class="card-preset-pill" data-set-card-qty="100">100<?= e($unit === 'meter' ? 'm' : 'yd') ?></button>
                                    <button type="button" class="card-preset-pill" data-set-card-qty="500">500<?= e($unit === 'meter' ? 'm' : 'yd') ?> (Bulk)</button>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <!-- B2B Bulk Order & Custom Weave Inquiry Box -->
        <section class="b2b-inquiry-box">
            <div class="inquiry-content">
                <h2>Custom Dyeing, Weaves &amp; Container Volume Orders</h2>
                <p>
                    Manufacturing clothing collections or exporting garments? Our textile operations team handles custom dyeing, lab dips, proprietary fiber blends, and container-scale shipments across Pakistan and internationally.
                </p>
            </div>
            <div class="inquiry-actions">
                <a class="button button-primary" href="tel:+923000000000">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>Contact Wholesale Desk</span>
                </a>
                <a class="button button-ghost" href="<?= e(url('/signup')) ?>">
                    <span>Register Business Account</span>
                </a>
            </div>
        </section>
    <?php endif; ?>
</section>
