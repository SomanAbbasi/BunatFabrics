<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Core\View;

/**
 * Handles the sign up, sign in and sign out screens.
 */
final class AuthController
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    public function showSignup(): void
    {
        if (Guard::check()) {
            redirect('/');
        }

        View::render('auth/signup', [
            'title'  => 'Create your account',
            'errors' => (array) Session::pullFlash('errors', []),
        ]);
    }

    public function signup(): void
    {
        $this->requireCsrf('/signup');

        $data = [
            'name'                  => trim(AuthSecurity::input('name')),
            'email'                 => trim(AuthSecurity::input('email')),
            'password'              => AuthSecurity::input('password'),
            'password_confirmation' => AuthSecurity::input('password_confirmation'),
        ];

        $result = $this->auth->register($data);

        if (isset($result['errors'])) {
            Session::flash('errors', $result['errors']);
            Session::flashInput($data);

            redirect('/signup');
        }

        Session::flash('status', 'Account created successfully. Please sign in.');

        redirect('/login');
    }

    public function showLogin(): void
    {
        if (Guard::check()) {
            redirect('/');
        }

        View::render('auth/login', [
            'title'  => 'Sign in',
            'errors' => (array) Session::pullFlash('errors', []),
        ]);
    }

    public function login(): void
    {
        $this->requireCsrf('/login');

        $email    = trim(AuthSecurity::input('email'));
        $password = AuthSecurity::input('password');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        $pdo = \App\Core\Database::connection();
        $allowed = AuthSecurity::allow($pdo, 'login-ip:' . AuthSecurity::ip(), 60, 900)
            && AuthSecurity::allow($pdo, 'login-email:' . strtolower($email), 20, 900);
        $user = $errors === [] && $allowed ? $this->auth->attempt($email, $password) : null;

        if ($user === null && $errors === []) {
            $errors['email'] = 'These credentials do not match our records.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flashInput(['email' => $email]);

            redirect('/login');
        }

        Guard::login($user);

        redirect(Session::pullIntendedUrl('/'));
    }

    public function logout(): void
    {
        $this->requireCsrf('/dashboard');

        Guard::logout();
        Session::destroy();

        redirect('/login');
    }

    /**
     * Reject a POST that is missing a valid CSRF token.
     */
    private function requireCsrf(string $fallback): void
    {
        if (! Session::verifyCsrf(AuthSecurity::input('_token'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            redirect($fallback);
        }
    }
}
