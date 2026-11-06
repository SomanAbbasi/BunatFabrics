<?php

declare(strict_types=1);

$settingsPage = true;
$settingsAdmin = false;
$settingsPath = '/settings';
?>
<section class="account-page">
    <header class="page-heading">
        <span class="eyebrow">Customer account</span>
        <h1>Account settings</h1>
        <p>Keep your contact details and sign-in password up to date.</p>
    </header>
    <?php require base_path('views/partials/account-settings.php'); ?>
</section>
