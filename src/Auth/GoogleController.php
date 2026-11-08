<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Env;
use App\Core\Session;
use DomainException;
use Throwable;

final class GoogleController
{
    public function start(): void
    {
        AuthSecurity::csrf();
        AuthSecurity::privateResponse();
        $mode = AuthSecurity::input('mode');
        if (!in_array($mode, ['login', 'link', 'verify'], true)) { $mode = 'login'; }
        $user = Guard::user();
        Session::forget('google_verified');
        try {
            $this->configuration();
            if (!AuthSecurity::allow(Database::connection(), 'google:' . AuthSecurity::ip(), 20, 900)) {
                throw new DomainException('Too many attempts. Please wait and try again.');
            }
            if ($mode === 'login' && $user !== null) { redirect('/dashboard'); }
            if ($mode !== 'login' && $user === null) { redirect('/login'); }
            if ($mode === 'link' && (!is_string($user['password']) || !password_verify(AuthSecurity::input('current_password'), $user['password']))) {
                throw new DomainException('Confirm your current password before connecting Google.');
            }
            if ($mode === 'verify' && !(new GoogleIdentityService())->linked((int) $user['id'])) {
                throw new DomainException('No Google account is connected.');
            }
            Session::put('google_flow', ['mode' => $mode, 'id' => $user['id'] ?? null, 'stamp' => $user ? AuthSecurity::stamp($user) : null, 'started' => time()]);
            // Never pass arbitrary request parameters into the library on initiation.
            $_REQUEST = [];
            $client = new GoogleOidc();
            // Google supports account selection, not forced Google-password reauthentication.
            $client->addAuthParam(['prompt' => 'select_account']);
            $client->authenticate();
        } catch (Throwable $exception) { $this->failure($exception, $user); }
    }

    public function callback(): void
    {
        AuthSecurity::privateResponse();
        $flow = Session::get('google_flow');
        Session::forget('google_flow');
        $user = Guard::user();
        Session::forget('google_verified');
        try {
            $this->configuration();
            $state = AuthSecurity::input('state', $_GET);
            $stored = Session::get('openid_connect_state');
            if (!is_array($flow) || time() - $flow['started'] > 600 || !is_string($stored) || $state === '' || !hash_equals($stored, $state)
                || AuthSecurity::input('error', $_GET) !== '' || AuthSecurity::input('code', $_GET) === '') {
                throw new DomainException('Google sign-in was cancelled or expired. Please try again.');
            }
            if ($flow['mode'] !== 'login' && (!$user || (int) $user['id'] !== (int) $flow['id'] || !hash_equals($flow['stamp'], AuthSecurity::stamp($user)))) {
                throw new DomainException('Your account session changed. Please sign in again.');
            }
            if ($flow['mode'] === 'login' && $user !== null) { throw new DomainException('Please start sign-in again.'); }
            $_REQUEST = ['state' => $state, 'code' => AuthSecurity::input('code', $_GET)];
            $client = new GoogleOidc();
            $client->authenticate();
            $claims = (array) $client->getVerifiedClaims();
            $identities = new GoogleIdentityService();
            if ($flow['mode'] === 'verify') {
                $identities->verifySettingsResponse((int) $user['id'], $claims, (int) $flow['started']);
            } else {
                $user = $identities->resolve($claims, $flow['mode'] === 'link' ? $user : null);
            }
            Guard::login($user);
            Session::forget('_csrf_token');
            if ($flow['mode'] === 'verify') {
                Session::put('google_verified', ['id' => $user['id'], 'stamp' => AuthSecurity::stamp($user), 'until' => time() + 300]);
                Session::flash('status', 'Google account verified. You can save your changes within five minutes.');
            }
            $this->clear();
            redirect($flow['mode'] === 'login' ? ($user['role'] === 'admin' ? '/admin' : '/dashboard') : $this->settings($user));
        } catch (Throwable $exception) { $this->failure($exception, $user); }
    }

    private function configuration(): void
    {
        if (!GoogleOidc::enabled()) { throw new DomainException('Google sign-in is not configured yet. Use your usual sign-in method.'); }
        $callback = (string) Env::get('GOOGLE_REDIRECT_URI', 'http://localhost/auth/google/callback');
        $origin = parse_url((string) config('app.url'));
        $target = parse_url($callback);
        if (!$origin || !$target || ($target['path'] ?? '') !== '/auth/google/callback'
            || ($origin['host'] ?? '') !== ($target['host'] ?? '') || ($origin['scheme'] ?? '') !== ($target['scheme'] ?? '')
            || ($origin['port'] ?? null) !== ($target['port'] ?? null)
            || (($target['scheme'] ?? '') !== 'https' && ($target['host'] ?? '') !== 'localhost')) {
            throw new DomainException('Google sign-in needs matching application and callback origins. Use localhost for local testing.');
        }
        // Avoid beginning a flow on bunat.local and returning to a different session on localhost.
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $expected = strtolower($target['host'] . (isset($target['port']) ? ':' . $target['port'] : ''));
        if ($host !== $expected) { throw new DomainException('Open the site at its configured localhost or HTTPS address before continuing with Google.'); }
    }

    private function settings(array $user): string { return $user['role'] === 'admin' ? '/admin/settings' : '/settings'; }

    private function clear(): void
    {
        foreach (['google_flow', 'openid_connect_state', 'openid_connect_nonce', 'openid_connect_code_verifier'] as $key) { Session::forget($key); }
    }

    private function failure(Throwable $exception, ?array $user): never
    {
        $this->clear();
        Session::forget('google_verified');
        Session::flash('error', $exception instanceof DomainException ? $exception->getMessage() : 'Google sign-in could not be completed. Please try again.');
        redirect($user ? $this->settings($user) : '/login');
    }
}
