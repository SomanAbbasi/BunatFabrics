<?php

declare(strict_types=1);
require __DIR__ . '/google_identity.php';

$pdo->exec('DELETE FROM auth_limits');
$settings = new App\Auth\AccountSettingsService($pdo, $mailer);
$customer = $auth->findByEmail('new-user@example.test');
$id = (int) $customer['id'];
verifySettings($settings->updateProfile($id, 'Verified Customer', 'verified@example.test', 'Recovery789') === [], 'email verification initiated');
preg_match('/code is: ([0-9]{6})/', end($mailer->messages)['body'], $otp);
verifySettings($settings->verifyEmail((int) $admin['id'], $otp[1]) !== [], 'OTP bound to account');
for ($attempt = 0; $attempt < 5; $attempt++) {
    verifySettings($settings->verifyEmail($id, 'invalid') !== [], 'invalid OTP rejected');
}
verifySettings($settings->verifyEmail($id, $otp[1]) !== [], 'attempt exhaustion rejects even correct OTP');
$pdo->exec('DELETE FROM auth_limits');
verifySettings($settings->updateProfile($id, 'Verified Customer', 'verified@example.test', 'Recovery789') === [], 'new OTP requested');
preg_match('/code is: ([0-9]{6})/', end($mailer->messages)['body'], $otp);
verifySettings($settings->verifyEmail($id, $otp[1]) === [], 'correct OTP accepted');
verifySettings($settings->verifyEmail($id, $otp[1]) !== [], 'OTP cannot be reused');
$googleId = (int) $created['id'];
verifySettings($settings->updatePassword($googleId, '', 'Established123', 'Established123') !== [], 'Google account cannot set password without reauthentication');
App\Core\Session::put('google_verified', ['id'=>$googleId, 'stamp'=>App\Auth\AuthSecurity::stamp($created), 'until'=>time()-1]);
verifySettings($settings->updatePassword($googleId, '', 'Established123', 'Established123') !== [], 'expired Google reauthentication rejected');
App\Core\Session::put('google_verified', ['id'=>$googleId, 'stamp'=>App\Auth\AuthSecurity::stamp($created), 'until'=>time()+300]);
verifySettings($settings->updatePassword($googleId, '', 'Established123', 'Established123') === [], 'fresh verified Google identity can establish password');
verifySettings(!App\Core\Session::has('google_verified'), 'Google proof consumed');
verifySettings($auth->attempt('google@example.test', 'Established123') !== null, 'established local password authenticates');
verifySettings($settings->updatePassword($googleId, '', 'Another123', 'Another123') !== [], 'local password required after it is established');
echo "PASS: OTP ownership, attempts, single-use and Google-only password establishment\n";
