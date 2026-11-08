<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Core\View;
use Throwable;

final class RecoveryController
{
    public function forgot(): void
    {
        AuthSecurity::privateResponse();
        View::render('auth/forgot', ['title' => 'Forgot password']);
    }

    public function request(): void
    {
        AuthSecurity::csrf();
        $started = microtime(true);
        try { (new RecoveryService())->request(AuthSecurity::input('email')); }
        catch (Throwable) { error_log('Password recovery request failed.'); }
        // Reduce obvious fast-path differences; SMTP timing can still vary.
        usleep((int) max(0, (0.4 - (microtime(true) - $started)) * 1000000));
        Session::flash('status', 'If this address has a password-based account, we will send a reset link. Google-only customers should continue with Google.');
        redirect('/forgot-password');
    }

    public function form(): void
    {
        AuthSecurity::privateResponse();
        $token = AuthSecurity::input('token', $_GET);
        if ($token !== '') {
            Session::put('reset_token', preg_match('/^[a-f0-9]{32}\.[a-f0-9]{64}$/D', $token) ? $token : '');
            redirect('/reset-password');
        }
        View::render('auth/reset', ['title' => 'Reset password', 'errors' => []]);
    }

    public function reset(): void
    {
        AuthSecurity::privateResponse();
        AuthSecurity::csrf();
        try {
            $errors = (new RecoveryService())->reset((string) Session::get('reset_token', ''), AuthSecurity::input('password'), AuthSecurity::input('password_confirmation'));
        } catch (Throwable) { $errors = ['token' => 'We could not reset your password. Please try again later.']; }
        if ($errors !== []) {
            http_response_code(422);
            View::render('auth/reset', ['title' => 'Reset password', 'errors' => $errors]);
            return;
        }
        Session::forget('reset_token');
        Guard::logout();
        Session::regenerate();
        Session::forget('_csrf_token');
        Session::flash('status', 'Your password has been changed. Sign in with your new password.');
        redirect('/login');
    }
}
