<?php

declare(strict_types=1);

namespace App\Admin\Settings;

use App\Auth\Guard;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use PDO;

final class SettingsController
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function index(): void
    {
        $user = Guard::requireAdmin();

        // Reload user from database to ensure fresh data
        $statement = $this->pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $user['id']]);
        $freshUser = $statement->fetch();

        View::render('admin/settings/index', [
            'title'     => 'Admin Settings',
            'user'      => $freshUser ?: $user,
            'activeNav' => 'settings',
            'errors'    => (array) Session::pullFlash('errors', []),
        ], 'admin/layout');
    }

    public function updateProfile(): void
    {
        $currentUser = Guard::requireAdmin();

        if (! Session::verifyCsrf($_POST['_token'] ?? null)) {
            Session::flash('error', 'Session expired. Please try again.');
            redirect('/admin/settings');
        }

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        $errors = (new Validator([
            'name'  => $name,
            'email' => $email,
        ]))->validate([
            'name'  => 'required|min:2|max:100',
            'email' => 'required|email|max:150',
        ], [
            'name'  => 'Name',
            'email' => 'Email address',
        ]);

        if ($errors === []) {
            // Check if email taken by another account
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $stmt->execute(['email' => $email, 'id' => $currentUser['id']]);
            if ($stmt->fetch() !== false) {
                $errors['email'] = 'This email is already in use by another account.';
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/settings');
        }

        $update = $this->pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        $update->execute([
            'name'  => $name,
            'email' => $email,
            'id'    => $currentUser['id'],
        ]);

        // Refresh session
        $currentUser['name'] = $name;
        $currentUser['email'] = $email;
        Guard::login($currentUser);

        Session::flash('status', 'Your account profile has been updated successfully.');
        redirect('/admin/settings');
    }

    public function updatePassword(): void
    {
        $currentUser = Guard::requireAdmin();

        if (! Session::verifyCsrf($_POST['_token'] ?? null)) {
            Session::flash('error', 'Session expired. Please try again.');
            redirect('/admin/settings');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['new_password_confirmation'] ?? '');

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'] = 'Current password is required.';
        }

        if (mb_strlen($newPassword) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters long.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['new_password_confirmation'] = 'Password confirmation does not match.';
        }

        if ($errors === []) {
            $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $currentUser['id']]);
            $hash = (string) $stmt->fetchColumn();

            if (! password_verify($currentPassword, $hash)) {
                $errors['current_password'] = 'Your current password was incorrect.';
            }
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/settings');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update  = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $update->execute([
            'password' => $newHash,
            'id'       => $currentUser['id'],
        ]);

        Session::flash('status', 'Password changed successfully.');
        redirect('/admin/settings');
    }
}
