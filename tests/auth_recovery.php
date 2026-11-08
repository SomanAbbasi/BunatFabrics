<?php

declare(strict_types=1);
require __DIR__ . '/account_settings.php';

$mailer = new TestAccountMailer();
$recovery = new App\Auth\RecoveryService($pdo, $mailer);
foreach (['new-admin@example.test', 'new-user@example.test'] as $email) {
    $recovery->request($email);
    $message = end($mailer->messages);
    preg_match('/token=([a-f0-9]{32}\.[a-f0-9]{64})/', $message['body'], $match);
    $token = $match[1] ?? '';
    verifySettings($token !== '', 'recovery mail contains a reset link');
    verifySettings($recovery->reset($token, 'Recovery789', 'Mismatch123') !== [], 'mismatch rejected');
    verifySettings($recovery->reset($token, 'Recovery789', 'Recovery789') === [], 'valid reset accepted');
    verifySettings($recovery->reset($token, 'Another123', 'Another123') !== [], 'reused reset rejected');
    verifySettings($auth->attempt($email, 'Changed456') === null && $auth->attempt($email, 'Recovery789') !== null, 'reset changes authentication');
    $count = count($mailer->messages);
    $recovery->request($email);
    verifySettings(count($mailer->messages) === $count, 'resend cooldown enforced');
}
$pdo->exec('DELETE FROM auth_limits');
$recovery->request('new-user@example.test');
$message = end($mailer->messages);
preg_match('/token=([a-f0-9]{32}\.[a-f0-9]{64})/', $message['body'], $match);
$pdo->exec("UPDATE auth_challenges SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR)");
verifySettings($recovery->reset($match[1], 'Another123', 'Another123') !== [], 'expired reset rejected');
verifySettings(App\Core\Migrator::run($pdo, base_path('database/migrations')) === [], 'auth migrations repeat safely');
echo "PASS: recovery, notification, single use, expiry, cooldown and migration checks\n";
