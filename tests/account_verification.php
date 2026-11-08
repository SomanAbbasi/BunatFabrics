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
verifySettings($settings->updateProfile($googleId, 'Updated Google Customer', $created['email'], '') === [], 'verified Google-only profile update succeeds');
verifySettings(!App\Core\Session::has('google_verified'), 'profile update consumes Google proof');
App\Core\Session::put('google_verified', ['id'=>$googleId, 'stamp'=>App\Auth\AuthSecurity::stamp($created), 'until'=>time()+300]);
verifySettings($settings->updatePassword($googleId, '', 'Established123', 'Established123') === [], 'fresh verified Google identity can establish password');
verifySettings(!App\Core\Session::has('google_verified'), 'Google proof consumed');
verifySettings($auth->attempt('google@example.test', 'Established123') !== null, 'established local password authenticates');
verifySettings($settings->updatePassword($googleId, '', 'Another123', 'Another123') !== [], 'local password required after it is established');
echo "PASS: OTP ownership, attempts, single-use and Google-only password establishment\n";
$pdo->exec('DELETE FROM auth_limits');
verifySettings($settings->updateProfile($id, 'Verified Customer', 'expired@example.test', 'Recovery789') === [], 'expiry test code issued');
preg_match('/code is: ([0-9]{6})/', end($mailer->messages)['body'], $otp);
$pdo->exec("UPDATE auth_challenges SET expires_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 SECOND) WHERE purpose='email'");
verifySettings($settings->verifyEmail($id, $otp[1]) !== [], 'expired OTP rejected');
verifySettings(App\Auth\AuthSecurity::allow($pdo, 'test-limit', 1, 60), 'first rate-limited action allowed');
verifySettings(!App\Auth\AuthSecurity::allow($pdo, 'test-limit', 1, 60), 'repeated action rate-limited');
$pdo->exec('DELETE FROM auth_limits');
$recovery->request('new-admin@example.test');
preg_match('/token=([a-f0-9]{32})\.([a-f0-9]{64})/', end($mailer->messages)['body'], $reset);
for ($i=0; $i<5; $i++) {
    verifySettings($recovery->reset($reset[1] . '.' . str_repeat('0',64), 'Attempt123', 'Attempt123') !== [], 'reset token guessing rejected');
}
verifySettings($recovery->reset($reset[1] . '.' . $reset[2], 'Attempt123', 'Attempt123') !== [], 'exhausted reset challenge rejected');
$client = new App\Auth\GoogleOidc();
$verify = new ReflectionMethod($client, 'verifyJWTClaims');
verifySettings(!$verify->invoke($client, (object) ['sub'=>'test']), 'OIDC missing required claims rejected');
verifySettings(!$verify->invoke($client, (object) ['sub'=>'test','iss'=>'https://accounts.google.com','aud'=>'test','iat'=>time(),'exp'=>time()-60,'nonce'=>'test']), 'OIDC expired claims rejected');
echo "PASS: OTP expiry, shared rate limits, reset guessing lockout and required OIDC claims\n";
$rejected = false;
try { $client->verifyJWTSignature(rtrim(strtr(base64_encode('{"alg":"none"}'), '+/', '-_'), '=') . '.e30.'); }
catch (RuntimeException) { $rejected = true; }
verifySettings($rejected, 'unsigned Google token rejected before network access');
echo "PASS: unsigned OIDC tokens rejected\n";
