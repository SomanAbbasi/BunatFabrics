<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Core\View;
use PDOException;

/** Shared request handling; the admin subclass requires administrator access. */
class AccountSettingsController
{
    protected const ADMIN = false;

    public function index(): void
    {
        $this->render($this->requireUser());
    }

    public function updateProfile(): void
    {
        $this->update('profile');
    }

    public function updatePassword(): void
    {
        $this->update('password');
    }

    private function update(string $section): void
    {
        $user = $this->requireUser();
        if (! Session::verifyCsrf(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : null)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Request expired']);
            return;
        }

        $name = trim($this->input('name'));
        $email = trim($this->input('email'));
        try {
            $service = new AccountSettingsService();
            $errors = $section === 'profile'
                ? $service->updateProfile((int) $user['id'], $name, $email, $this->input('current_password'))
                : $service->updatePassword((int) $user['id'], $this->input('current_password'), $this->input('new_password'), $this->input('new_password_confirmation'));
            if ($errors === []) {
                $freshUser = (new AuthService())->find((int) $user['id']);
                if ($freshUser === null) {
                    Guard::logout();
                    redirect('/login');
                }
                // Keep this verified session signed in, with a new ID and CSRF token.
                Guard::login($freshUser);
                Session::forget('_csrf_token');
                Session::flash('status', $section === 'profile' ? 'Your profile has been updated.' : 'Your password has been changed.');
                redirect($this->path());
            }
            http_response_code(422);
        } catch (PDOException $exception) {
            error_log('Account settings update failed: ' . $exception->getCode());
            $errors = ['_form' => 'We could not save your changes. Please try again later.'];
            http_response_code(503);
        }

        // Only non-secret profile fields are returned to the view, never submitted passwords.
        $this->render($user, $errors, $section, $section === 'profile' ? ['name' => $name, 'email' => $email] : []);
    }

    private function requireUser(): array
    {
        if (static::ADMIN) {
            return Guard::requireAdmin();
        }
        $user = Guard::user();
        if ($user === null) {
            redirect('/login');
        }
        if ($user['role'] === 'admin') {
            redirect('/admin/settings');
        }
        return $user;
    }

    private function render(array $user, array $errors = [], string $section = '', array $profile = []): void
    {
        $safeUser = array_intersect_key($user, array_flip(['id', 'name', 'email', 'role', 'created_at']));
        View::render(static::ADMIN ? 'admin/settings/index' : 'settings/index', [
            'title' => 'Account settings', 'activeNav' => 'settings', 'user' => $safeUser,
            'settingsPath' => $this->path(), 'profile' => $profile + $safeUser,
            'profileErrors' => $section === 'profile' ? $errors : [],
            'passwordErrors' => $section === 'password' ? $errors : [],
        ], static::ADMIN ? 'admin/layout' : 'layout');
    }

    private function path(): string
    {
        return static::ADMIN ? '/admin/settings' : '/settings';
    }

    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
    }
}
