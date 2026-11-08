<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use DomainException;
use PDO;
use Throwable;

final class GoogleIdentityService
{
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? Database::connection(); }

    /** Claims must come from GoogleOidc's signature and protocol validation. */
    public function resolve(array $claims, ?array $linkAccount = null): array
    {
        $subject = $claims['sub'] ?? '';
        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if (!is_string($subject) || !preg_match('/^[a-zA-Z0-9_-]{1,255}$/D', $subject)
            || ($claims['email_verified'] ?? false) !== true || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Google did not provide a verified account.');
        }
        $this->pdo->beginTransaction();
        try {
            if ($linkAccount !== null) {
                $statement = $this->pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');
                $statement->execute([$linkAccount['id']]);
                $current = $statement->fetch();
                if (!$current || !hash_equals(AuthSecurity::stamp($linkAccount), AuthSecurity::stamp($current))) {
                    throw new DomainException('Your account changed. Verify it again before linking.');
                }
            }
            $statement = $this->pdo->prepare('SELECT u.* FROM google_identities g JOIN users u ON u.id=g.user_id WHERE g.subject=?');
            $statement->execute([$subject]);
            $user = $statement->fetch();
            if ($user && $linkAccount !== null && (int) $user['id'] !== (int) $linkAccount['id']) {
                throw new DomainException('This Google account is already connected to another account.');
            }
            if (!$user && $linkAccount !== null) {
                $user = $current;
            } elseif (!$user) {
                if ((new AuthService($this->pdo))->findByEmail($email) !== null) {
                    throw new DomainException('Sign in using your existing method, then connect Google in Account settings.');
                }
                $name = trim((string) ($claims['name'] ?? 'Google customer'));
                $name = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '', 0, 100);
                if (mb_strlen($name) < 2) { $name = 'Google customer'; }
                $statement = $this->pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,NULL,'user')");
                $statement->execute([$name, $email]);
                $user = (new AuthService($this->pdo))->find((int) $this->pdo->lastInsertId());
            }
            $statement = $this->pdo->prepare('SELECT subject FROM google_identities WHERE user_id=?');
            $statement->execute([$user['id']]);
            $existing = $statement->fetchColumn();
            if ($existing !== false && $existing !== $subject) {
                throw new DomainException('A different Google account is already connected.');
            }
            if ($existing === false) {
                $this->pdo->prepare('INSERT INTO google_identities (subject,user_id) VALUES (?,?)')->execute([$subject, $user['id']]);
            }
            $this->pdo->commit();
            return $user;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function linked(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM google_identities WHERE user_id=?');
        $statement->execute([$id]);
        return (bool) $statement->fetchColumn();
    }

    public function matches(int $id, string $subject): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM google_identities WHERE user_id=? AND subject=?');
        $statement->execute([$id, $subject]);
        return (bool) $statement->fetchColumn();
    }
}
