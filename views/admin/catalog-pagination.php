<?php

declare(strict_types=1);

?>
<nav class="d-flex gap-3 align-items-center mt-3" aria-label="List pages">
    <?php if ($page > 1): ?><a class="btn" href="<?= e(url('/admin/' . $activeNav . '?page=' . ($page - 1))) ?>">Previous</a><?php endif; ?>
    <span class="text-secondary">Page <?= e($page) ?></span>
    <?php if ($hasNext): ?><a class="btn" href="<?= e(url('/admin/' . $activeNav . '?page=' . ($page + 1))) ?>">Next</a><?php endif; ?>
</nav>
