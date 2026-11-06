<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Validator;
use PDO;
use PDOException;

/** Account updates always use the ID supplied by the authenticated controller. */
final class AccountSettingsService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function updateProfile(int $id, string $name, string $email, string $currentPassword): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $errors = (new Validator(['name' => $name, 'email' => $email]))->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:190',
        ], ['name' => 'Name', 'email' => 'Email address']);
        if (! mb_check_encoding($name, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            $errors['name'] = 'Enter a valid name without control characters.';
        }

        $hash = $this->verifiedHash($id, $currentPassword);
        if ($hash === null) {
            $errors['current_password'] = 'Enter your correct current password to save changes.';
        }
        if ($errors !== []) {
            return $errors;
        }

        try {
            // Include the verified hash so a concurrent password change invalidates this update.
            $statement = $this->pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND password = ?');
            $statement->execute([$name, $email, $id, $hash]);
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                return ['email' => 'That email address is already registered.'];
            }
            throw $exception;
        }

        // MySQL reports zero changed rows when the submitted profile is unchanged.
        if ($statement->rowCount() === 0 && $this->storedHash($id) !== $hash) {
            return ['current_password' => 'Your account changed in another request. Please try again.'];
        }
        return [];
    }

    public function updatePassword(int $id, string $currentPassword, string $password, string $confirmation): array
    {
        $errors = (new Validator(['new_password' => $password]))->validate([
            'new_password' => 'required|password',
        ], ['new_password' => 'New password']);
        // Bcrypt only considers the first 72 bytes. Reject truncation and invalid input.
        if (strlen($password) > 72 || str_contains($password, "\0") || ! mb_check_encoding($password, 'UTF-8')) {
            $errors['new_password'] = 'Use a valid password of at most 72 bytes, without null characters.';
        }
        if ($confirmation === '' || $password !== $confirmation) {
            $errors['new_password_confirmation'] = 'Enter the same new password in both fields.';
        }
        $hash = $this->verifiedHash($id, $currentPassword);
        if ($hash === null) {
            $errors['current_password'] = 'Enter your correct current password to save changes.';
        } elseif (! isset($errors['new_password']) && password_verify($password, $hash)) {
            $errors['new_password'] = 'Choose a password different from your current password.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $statement = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND password = ?');
        $statement->execute([password_hash($password, PASSWORD_BCRYPT), $id, $hash]);
        return $statement->rowCount() === 1 ? [] : [
            'current_password' => 'Your account changed in another request. Please try again.',
        ];
    }

    private function storedHash(int $id): ?string
    {
        $statement = $this->pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $hash = $statement->fetchColumn();
        return is_string($hash) ? $hash : null;
    }

    private function verifiedHash(int $id, string $password): ?string
    {
        if ($password === '' || strlen($password) > 4096 || str_contains($password, "\0")) {
            return null;
        }
        $hash = $this->storedHash($id);
        return $hash !== null && password_verify($password, $hash) ? $hash : null;
    }
}
