<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use PDO;
use PDOException;
use Throwable;

/** All account IDs originate in Guard, never in submitted form fields. */
final class AccountSettingsService
{
    private PDO $pdo;
    private AccountMailer $mailer;
    public function __construct(?PDO $pdo = null, ?AccountMailer $mailer = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->mailer = $mailer ?? new AccountMailer();
    }

    public function updateProfile(int $id, string $name, string $email, string $currentPassword): array
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $errors = (new Validator(['name' => $name, 'email' => $email]))->validate([
            'name' => 'required|min:2|max:100', 'email' => 'required|email|max:190',
        ]);
        if (!mb_check_encoding($name, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            $errors['name'] = 'Enter a valid name without control characters.';
        }
        if ($errors !== []) { return $errors; }
        $this->pdo->beginTransaction();
        try {
            $user = $this->lockUser($id);
            if (!$this->authorized($user, $currentPassword)) {
                $this->pdo->commit(); return $this->verificationError();
            }
            if ($email !== $user['email']) {
                $duplicate = (new AuthService($this->pdo))->findByEmail($email);
                if ($duplicate !== null && (int) $duplicate['id'] !== $id) {
                    $this->pdo->commit(); return ['email' => 'That email address is already registered.'];
                }
                if (!AuthSecurity::allow($this->pdo, 'email-code:' . $id, 1, 60)
                    || !AuthSecurity::allow($this->pdo, 'email-hour:' . $id, 5, 3600)) {
                    $this->pdo->commit(); return ['email' => 'Please wait before requesting another verification code.'];
                }
                $code = (string) random_int(100000, 999999);
                $this->pdo->prepare("DELETE FROM auth_challenges WHERE user_id=? AND purpose='email'")->execute([$id]);
                $statement = $this->pdo->prepare("INSERT INTO auth_challenges (id,user_id,purpose,secret_hash,payload,account_stamp,expires_at) VALUES (?,?,'email',?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))");
                $statement->execute([bin2hex(random_bytes(16)), $id, password_hash($code, PASSWORD_BCRYPT), $email, AuthSecurity::stamp($user)]);
                $this->mailer->send($email, 'Verify your new Bunat Fabrics email', "Your verification code is: $code\n\nIt expires in 10 minutes. Do not share it. Your account email will stay unchanged until you confirm this code.");
            }
            $this->pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $id]);
            $this->pdo->commit();
            Session::forget('google_verified');
            return [];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function updatePassword(int $id, string $currentPassword, string $password, string $confirmation): array
    {
        $errors = AuthSecurity::passwordErrors($password, $confirmation);
        if ($errors !== []) {
            return array_combine(array_map(static fn ($key) => 'new_' . $key, array_keys($errors)), array_values($errors));
        }
        $this->pdo->beginTransaction();
        try {
            $user = $this->lockUser($id);
            if (!$this->authorized($user, $currentPassword)) {
                $this->pdo->commit(); return $this->verificationError();
            }
            if ($user['password'] !== null && password_verify($password, $user['password'])) {
                $this->pdo->commit(); return ['new_password' => 'Choose a password different from your current password.'];
            }
            $this->pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
            $this->pdo->prepare('DELETE FROM auth_challenges WHERE user_id=?')->execute([$id]);
            $this->pdo->commit();
            Session::forget('google_verified');
            $this->mailer->passwordChanged($user['email']);
            return [];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function verifyEmail(int $id, string $code): array
    {
        $failure = ['_form' => 'The code is incorrect, expired or used. Request a new code if needed.'];
        if (!AuthSecurity::allow($this->pdo, 'email-attempt-ip:' . AuthSecurity::ip(), 30, 900)) { return $failure; }
        $this->pdo->beginTransaction();
        try {
            $user = $this->lockUser($id);
            $statement = $this->pdo->prepare("SELECT * FROM auth_challenges WHERE user_id=? AND purpose='email' AND expires_at>UTC_TIMESTAMP() FOR UPDATE");
            $statement->execute([$id]);
            $challenge = $statement->fetch();
            if (!$user || !$challenge || $challenge['attempts'] >= 5 || !hash_equals($challenge['account_stamp'], AuthSecurity::stamp($user))) {
                $this->pdo->commit(); return $failure;
            }
            $this->pdo->prepare('UPDATE auth_challenges SET attempts=attempts+1 WHERE id=?')->execute([$challenge['id']]);
            if (!preg_match('/^[0-9]{6}$/D', $code) || !password_verify($code, $challenge['secret_hash'])) {
                $this->pdo->commit(); return $failure;
            }
            $this->pdo->prepare('UPDATE users SET email=? WHERE id=?')->execute([$challenge['payload'], $id]);
            $this->pdo->prepare('DELETE FROM auth_challenges WHERE user_id=?')->execute([$id]);
            $this->pdo->commit();
            Session::forget('google_verified');
            try { $this->mailer->send($user['email'], 'Your Bunat Fabrics email changed', 'Your sign-in email address was changed. Contact the business if this was not you.'); }
            catch (Throwable) { error_log('Account email-change notification failed.'); }
            return [];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            if (($exception->errorInfo[1] ?? null) === 1062) { return ['_form' => 'That email address is no longer available.']; }
            throw $exception;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $exception;
        }
    }

    public function pendingEmail(int $id): ?string
    {
        $statement = $this->pdo->prepare("SELECT payload FROM auth_challenges WHERE user_id=? AND purpose='email' AND expires_at>UTC_TIMESTAMP() AND attempts<5 LIMIT 1");
        $statement->execute([$id]);
        $value = $statement->fetchColumn();
        return is_string($value) ? $value : null;
    }

    private function lockUser(int $id): array|false
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');
        $statement->execute([$id]);
        return $statement->fetch();
    }

    private function authorized(array|false $user, string $password): bool
    {
        if (!$user || !AuthSecurity::allow($this->pdo, 'settings:' . $user['id'], 20, 900)) { return false; }
        if ($user['password'] !== null) {
            return $password !== '' && strlen($password) <= 4096 && !str_contains($password, "\0") && password_verify($password, $user['password']);
        }
        $proof = Session::get('google_verified');
        return is_array($proof) && (int) $proof['id'] === (int) $user['id'] && $proof['until'] > time()
            && hash_equals($proof['stamp'], AuthSecurity::stamp($user));
    }

    private function verificationError(): array
    {
        return ['current_password' => 'Verify your current password or connected Google account. If you have tried repeatedly, wait 15 minutes.'];
    }
}
