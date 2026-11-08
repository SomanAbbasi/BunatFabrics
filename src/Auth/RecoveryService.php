<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use PDO;
use Throwable;

final class RecoveryService
{
    private PDO $pdo;
    private AccountMailer $mailer;

    public function __construct(?PDO $pdo = null, ?AccountMailer $mailer = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->mailer = $mailer ?? new AccountMailer();
    }

    public function request(string $email): void
    {
        $email = strtolower(trim($email));
        if (!AuthSecurity::allow($this->pdo, 'reset-ip:' . AuthSecurity::ip(), 10, 900)
            || !AuthSecurity::allow($this->pdo, 'reset-email:' . $email, 1, 60)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)) { return; }
        $user = (new AuthService($this->pdo))->findByEmail($email);
        // Google-only users establish a password after Google reauthentication.
        if ($user === null || empty($user['password'])) { return; }
        $id = bin2hex(random_bytes(16));
        $secret = bin2hex(random_bytes(32));
        $statement = $this->pdo->prepare("INSERT INTO auth_challenges (id,user_id,purpose,secret_hash,account_stamp,expires_at) VALUES (?,?,'reset',?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))");
        $statement->execute([$id, $user['id'], hash('sha256', $secret), AuthSecurity::stamp($user)]);
        try {
            $this->mailer->send($email, 'Reset your Bunat Fabrics password', "Use this link within 30 minutes. Ignore this email if you did not request it.\n\n" . url('/reset-password') . '?token=' . $id . '.' . $secret);
        } catch (Throwable) {
            $this->pdo->prepare('DELETE FROM auth_challenges WHERE id=?')->execute([$id]);
            error_log('Password recovery email delivery failed.');
        }
    }

    public function reset(string $token, string $password, string $confirmation): array
    {
        $errors = AuthSecurity::passwordErrors($password, $confirmation);
        if ($errors !== []) { return $errors; }
        $failure = ['token' => 'This link is invalid or expired. Request a new reset email.'];
        if (!AuthSecurity::allow($this->pdo, 'reset-attempt:' . AuthSecurity::ip(), 20, 900)
            || !preg_match('/^([a-f0-9]{32})\.([a-f0-9]{64})$/D', $token, $parts)) { return $failure; }
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare("SELECT * FROM auth_challenges WHERE id=? AND purpose='reset' AND expires_at>UTC_TIMESTAMP() FOR UPDATE");
            $statement->execute([$parts[1]]);
            $challenge = $statement->fetch();
            if (!$challenge || $challenge['attempts'] >= 5) {
                $this->pdo->rollBack(); return $failure;
            }
            $this->pdo->prepare('UPDATE auth_challenges SET attempts=attempts+1 WHERE id=?')->execute([$parts[1]]);
            if (!hash_equals($challenge['secret_hash'], hash('sha256', $parts[2]))) {
                $this->pdo->commit(); return $failure;
            }
            $statement = $this->pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');
            $statement->execute([$challenge['user_id']]);
            $user = $statement->fetch();
            if (!$user || !hash_equals($challenge['account_stamp'], AuthSecurity::stamp($user))) {
                $this->pdo->commit(); return $failure;
            }
            if (password_verify($password, (string) $user['password'])) {
                $this->pdo->commit(); return ['password' => 'Choose a different password.'];
            }
            $this->pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);
            $this->pdo->prepare('DELETE FROM auth_challenges WHERE user_id=?')->execute([$user['id']]);
            $this->pdo->commit();
            $this->mailer->passwordChanged($user['email']);
            return [];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }
}
