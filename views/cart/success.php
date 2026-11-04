<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Order placed</span>
        <h1>Thank you, <?= e($user['name']) ?></h1>
        <p>Your wholesale order has been received.</p>
    </header>

    <div class="panel order-confirmation">
        <span class="confirmation-icon"><?= icon('check-circle') ?></span>
        <h2>Order #<?= e((string) $order['order_no']) ?> is pending confirmation</h2>
        <p class="muted">Our team will contact you on <strong><?= e($order['delivery_phone']) ?></strong> to confirm stock and delivery.</p>
        <p class="muted">Total: <strong><?= e(pkr($order['subtotal'])) ?></strong></p>
        <a class="button button-compact" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Browse more fabrics</span></a>
    </div>
</section>
