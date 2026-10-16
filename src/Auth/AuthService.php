<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Validator;
use PDO;

/**
 * All user account behaviour: registering, authenticating and looking up
 * users. Passwords only ever leave this class as bcrypt hashes.
 */
final class AuthService
{
    private PDO $pdo;

    public function __construct(?PDO $connection = null)
    {
        $this->pdo = $connection ?? Database::connection();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);

        return $statement->fetch() ?: null;
    }

    /**
     * Validate and persist a new customer account.
     *
     * @param array<string, mixed> $data
     * @return array{errors?: array<string, string>, user?: array}
     */
    public function register(array $data): array
    {
        $errors = (new Validator($data))->validate(
            [
                'name'     => 'required|min:2|max:100',
                'email'    => 'required|email|max:190',
                'password' => 'required|password|confirmed',
            ],
            [
                'name'     => 'Name',
                'email'    => 'Email',
                'password' => 'Password',
            ],
        );

        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if (! isset($errors['email']) && $this->findByEmail($email) !== null) {
            $errors['email'] = 'That email address is already registered.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role)
             VALUES (:name, :email, :password, :role)'
        );

        $statement->execute([
            'name'     => trim((string) $data['name']),
            'email'    => $email,
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'     => 'user',
        ]);

        return ['user' => $this->find((int) $this->pdo->lastInsertId())];
    }

    /**
     * Verify credentials and return the matching user, or null on failure.
     */
    public function attempt(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null || ! password_verify($password, (string) $user['password'])) {
            return null;
        }

        if (password_needs_rehash((string) $user['password'], PASSWORD_BCRYPT)) {
            $update = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $update->execute([
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'id'       => $user['id'],
            ]);
        }

        return $user;
    }
}
