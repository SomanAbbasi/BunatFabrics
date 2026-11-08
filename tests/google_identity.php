<?php

declare(strict_types=1);
require __DIR__ . '/auth_recovery.php';
$google = new App\Auth\GoogleIdentityService($pdo);
$claims = ['sub' => 'test-google-subject', 'email' => 'google@example.test', 'email_verified' => true, 'name' => 'Google Customer'];
$created = $google->resolve($claims);
verifySettings($created['role'] === 'user' && $created['password'] === null, 'Google registration creates passwordless customer');
verifySettings($google->resolve($claims)['id'] === $created['id'], 'subject resolves same account');
try {
    $google->resolve($claims + ['role' => 'admin'], $auth->findByEmail('new-admin@example.test'));
    throw new RuntimeException('Foreign identity was linked');
} catch (DomainException) {}
try {
    $google->resolve(['sub'=>'other-subject','email'=>'new-admin@example.test','email_verified'=>true]);
    throw new RuntimeException('Email auto-linked');
} catch (DomainException) {}
$admin = $auth->findByEmail('new-admin@example.test');
$linked = $google->resolve(['sub'=>'admin-subject','email'=>'different@example.test','email_verified'=>true], $admin);
verifySettings($linked['id'] === $admin['id'] && $linked['role'] === 'admin' && $linked['email'] === $admin['email'], 'explicit linking preserves local identity and role');
verifySettings(!$google->matches((int) $created['id'], 'admin-subject'), 'reauth subject ownership enforced');
try {
    $google->resolve(['sub'=>'unverified','email'=>'unverified@example.test','email_verified'=>false]);
    throw new RuntimeException('Unverified Google email accepted');
} catch (DomainException) {}
verifySettings($auth->attempt('google@example.test', '') === null, 'Google-only account rejects password login');
echo "PASS: Google subject identity, no email auto-linking, ownership and role preservation\n";
