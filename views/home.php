<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}> $allGroups */
/** @var list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}> $groups */
/** @var int|null $activeCategory */
/** @var array<int, int> $cartQuantities */
$totalProducts = array_sum(array_map(static fn (array $group): int => count($group['products']), $allGroups));
$resultCount = array_sum(array_map(static fn (array $group): int => count($group['products']), $groups));
?>
<section class="storefront">
    <header class="store-hero">
        <div class="store-hero-content">
            <span class="eyebrow">B2B Wholesale Fabric Sourcing</span>
            <h1>Fabric Collections</h1>
            <p>Browse collections, review specifications (width, weight, composition), and enter custom wholesale meterage.</p>
        </div>
    </header>

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
        <form class="catalog-search" action="<?= e(url('/')) ?>" method="get" role="search" aria-label="Fabric catalog">
            <label for="fabric-search" class="visually-hidden">Search fabrics</label>
            <div class="catalog-search-controls">
                <span class="catalog-search-icon" aria-hidden="true"><?= icon('search') ?></span>
                <input id="fabric-search" type="search" name="q" value="<?= e($search) ?>" maxlength="100" placeholder="Try cotton, silk, or product code...">
                <?php if ($activeCategory !== null): ?>
                    <input type="hidden" name="category" value="<?= e((string) $activeCategory) ?>">
                <?php endif; ?>
                <button type="submit" class="search-submit-btn">Search</button>
                <?php if ($search !== ''): ?>
                    <a class="search-clear-link" href="<?= e(url($activeCategory === null ? '/' : '/?category=' . $activeCategory)) ?>">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <nav class="category-nav" aria-label="Filter fabrics by category">
            <a class="category-pill <?= $activeCategory === null ? 'is-active' : '' ?>" href="<?= e(url($search === '' ? '/' : '/?' . http_build_query(['q' => $search]))) ?>" <?= $activeCategory === null ? 'aria-current="page"' : '' ?>>
                All Fabrics
                <span class="pill-count"><?= e((string) $totalProducts) ?></span>
            </a>
            <?php foreach ($allGroups as $group): ?>
                <a class="category-pill <?= $activeCategory === $group['id'] ? 'is-active' : '' ?>" href="<?= e(url('/?' . http_build_query(array_filter(['category' => $group['id'], 'q' => $search], static fn ($value): bool => $value !== '')))) ?>" <?= $activeCategory === $group['id'] ? 'aria-current="page"' : '' ?>>
                    <?= e($group['name']) ?>
                    <span class="pill-count"><?= e((string) count($group['products'])) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($search !== ''): ?>
            <p class="catalog-search-summary" role="status">
                <?= e((string) $resultCount) ?> <?= $resultCount === 1 ? 'fabric' : 'fabrics' ?> found for “<?= e($search) ?>”<?= $activeCategory !== null ? ' in this category' : '' ?>.
                <span>Category counts show all available fabrics.</span>
            </p>
        <?php endif; ?>

        <?php if ($groups === []): ?>
            <div class="empty-note">
                <p class="empty-note-title"><?= $search !== '' ? 'No fabrics match your search' : 'No fabrics in this category yet' ?></p>
                <p class="muted"><?= $search !== '' ? 'Check the spelling or try a different name, product code, material, or category.' : 'Try another category.' ?> <a href="<?= e(url('/')) ?>">View all fabrics</a>.</p>
                <?php if ($search !== '' && $activeCategory !== null && $matchesAcrossCategories > 0): ?>
                    <p><a href="<?= e(url('/?' . http_build_query(['q' => $search]))) ?>">Search all categories for “<?= e($search) ?>”</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($groups as $group): ?>
            <section class="category-section" id="category-<?= e((string) $group['id']) ?>" aria-labelledby="category-<?= e((string) $group['id']) ?>-title">
                <header class="section-heading">
                    <h2 id="category-<?= e((string) $group['id']) ?>-title"><?= e($group['name']) ?></h2>
                    <span><?= e((string) count($group['products'])) ?> <?= count($group['products']) === 1 ? 'fabric' : 'fabrics' ?></span>
                </header>

                <div class="product-grid">
                    <?php foreach ($group['products'] as $product): ?>
                        <?php
                        $inCart = (int) ($cartQuantities[(int) $product['id']] ?? 0);
                        $unit = (string) ($product['unit'] ?? 'meter');
                        ?>
                        <article class="product-card">
                            <a class="product-card-link" href="<?= e(url('/product?' . http_build_query(array_filter(['id' => $product['id'], 'q' => $search, 'category' => $activeCategory], static fn ($value): bool => $value !== '' && $value !== null)))) ?>">
                                <div class="product-media">
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
                                        <span class="unit-suffix">/ <?= e($unit) ?></span>
                                    </p>

                                    <dl class="product-meta">
                                        <?php if ($product['width_inch'] !== null): ?>
                                            <div><dt>Width</dt><dd><?= e((string) $product['width_inch']) ?> in</dd></div>
                                        <?php endif; ?>
                                        <?php if ($product['weight_gsm'] !== null): ?>
                                            <div><dt>Weight</dt><dd><?= e((string) $product['weight_gsm']) ?> GSM</dd></div>
                                        <?php endif; ?>
                                        <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?>
                                            <div><dt>Fiber</dt><dd><?= e($product['composition']) ?></dd></div>
                                        <?php endif; ?>
                                        <div><dt>Unit</dt><dd><?= e(ucfirst($unit)) ?></dd></div>
                                    </dl>
                                </div>
                            </a>

                            <!-- Simple, clean wholesale quantity order form -->
                            <form action="<?= e(url('/cart/add')) ?>" method="POST" class="product-card-cart" data-cart-form>
                                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                <input type="hidden" name="return_to" value="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">

                                <div class="card-order-controls">
                                    <div class="card-qty-group">
                                        <label for="qty-<?= e((string) $product['id']) ?>" class="visually-hidden">Quantity</label>
                                        <input type="number" id="qty-<?= e((string) $product['id']) ?>" name="quantity" value="1" min="1" max="1000000" class="card-qty-input" inputmode="numeric" title="Enter quantity in <?= e($unit) ?>s">
                                        <span class="card-qty-unit"><?= e($unit) ?>s</span>
                                    </div>
                                    <button type="submit" class="card-cart-button<?= $inCart > 0 ? ' is-added' : '' ?>" title="<?= $inCart > 0 ? 'In cart (' . $inCart . ' ' . $unit . 's)' : 'Add to cart' ?>">
                                        <?= icon($inCart > 0 ? 'check-circle' : 'plus') ?>
                                        <span class="btn-text"><?= $inCart > 0 ? 'In cart (' . $inCart . ')' : 'Add to cart' ?></span>
                                    </button>
                                </div>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
