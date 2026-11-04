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
    <header class="store-hero">
        <span class="eyebrow">Wholesale fabrics</span>
        <h1>Fabric collections for your business</h1>
        <p>Browse by category, compare specifications and PKR pricing, then open any fabric for the full details.</p>
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
        <nav class="category-nav" aria-label="Filter fabrics by category">
            <a class="<?= $activeCategory === null ? 'is-active' : '' ?>" href="<?= e(url('/')) ?>" <?= $activeCategory === null ? 'aria-current="page"' : '' ?>>
                All
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
                <p class="empty-note-title">No fabrics in this category yet</p>
                <p class="muted">Try another category or <a href="<?= e(url('/')) ?>">view all fabrics</a>.</p>
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
                        <?php $inCart = (int) ($cartQuantities[(int) $product['id']] ?? 0); ?>
                        <article class="product-card">
                            <a class="product-card-link" href="<?= e(url('/product?id=' . $product['id'])) ?>">
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
                                    <p class="product-price"><?= e(pkr($product['price_pkr'])) ?></p>
                                    <dl class="product-meta">
                                        <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?><div><dt>Composition</dt><dd><?= e($product['composition']) ?></dd></div><?php endif; ?>
                                        <?php if ($product['width_inch'] !== null): ?><div><dt>Width</dt><dd><?= e($product['width_inch']) ?> in</dd></div><?php endif; ?>
                                        <?php if ($product['weight_gsm'] !== null): ?><div><dt>Weight</dt><dd><?= e($product['weight_gsm']) ?> GSM</dd></div><?php endif; ?>
                                    </dl>
                                </div>
                            </a>

                            <form action="<?= e(url('/cart/add')) ?>" method="POST" class="product-card-cart">
                                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="card-cart-button<?= $inCart > 0 ? ' is-added' : '' ?>" title="<?= $inCart > 0 ? 'Add one more to your cart' : 'Add this fabric to your cart' ?>">
                                    <?= icon($inCart > 0 ? 'cart' : 'plus') ?>
                                    <span><?= $inCart > 0 ? 'In cart' : 'Add to cart' ?></span>
                                    <?php if ($inCart > 0): ?><span class="card-cart-count"><?= e((string) $inCart) ?></span><?php endif; ?>
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
