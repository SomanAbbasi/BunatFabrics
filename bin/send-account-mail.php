<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../bootstrap.php';
try {
    $count = (new App\Auth\AccountMailer())->deliverPending();
    echo "Delivered {$count} queued account emails.\n";
} catch (Throwable) {
    fwrite(STDERR, "Account mail worker failed. Check mail configuration.\n");
    exit(1);
}
