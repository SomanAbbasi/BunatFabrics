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
            redirect('/dashboard');
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
            'name'                  => trim((string) ($_POST['name'] ?? '')),
            'email'                 => trim((string) ($_POST['email'] ?? '')),
            'password'              => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
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
            redirect('/dashboard');
        }

        View::render('auth/login', [
            'title'  => 'Sign in',
            'errors' => (array) Session::pullFlash('errors', []),
        ]);
    }

    public function login(): void
    {
        $this->requireCsrf('/login');

        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        $user = $errors === [] ? $this->auth->attempt($email, $password) : null;

        if ($user === null && $errors === []) {
            $errors['email'] = 'These credentials do not match our records.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flashInput(['email' => $email]);

            redirect('/login');
        }

        Guard::login($user);

        redirect('/dashboard');
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
        if (! Session::verifyCsrf($_POST['_token'] ?? null)) {
            Session::flash('error', 'Your session expired. Please try again.');

            redirect($fallback);
        }
    }
}
