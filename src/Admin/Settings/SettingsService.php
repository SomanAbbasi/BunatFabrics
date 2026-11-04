<?php

declare(strict_types=1);

namespace App\Admin\Settings;

use App\Core\Database;
use App\Core\Validator;
use PDO;
use PDOException;

final class SettingsService
{
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? Database::connection(); }

    public function profile(int $id, string $name, string $email): array
    {
        $name = trim($name); $email = strtolower(trim($email));
        $errors = (new Validator(['name'=>$name, 'email'=>$email]))->validate(['name'=>'required|min:2|max:100','email'=>'required|email|max:190']);
        $st = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?'); $st->execute([$email, $id]);
        if ($st->fetchColumn() !== false) $errors['email'] = 'That email address is already registered.';
        if ($errors === []) {
            try { $st = $this->pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'admin'"); $st->execute([$name, $email, $id]); }
            catch (PDOException $e) { if (($e->errorInfo[1] ?? null) !== 1062) throw $e; $errors['email'] = 'That email address is already registered.'; }
        }
        return $errors;
    }

    public function password(int $id, string $current, string $password, string $confirmation): array
    {
        $errors = (new Validator(['password'=>$password,'password_confirmation'=>$confirmation]))->validate(['password'=>'required|password|confirmed']);
        $st = $this->pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'"); $st->execute([$id]); $hash = $st->fetchColumn();
        if (! is_string($hash) || ! password_verify($current, $hash)) $errors['current_password'] = 'Current password is incorrect.';
        if ($errors === []) {
            $st = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin' AND password = ?");
            $st->execute([password_hash($password, PASSWORD_BCRYPT), $id, $hash]);
            if ($st->rowCount() !== 1) $errors['current_password'] = 'Password changed in another request. Try again.';
        }
        return $errors;
    }
}
