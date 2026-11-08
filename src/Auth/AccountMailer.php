<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Env;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

class AccountMailer
{
    public function send(string $email, string $subject, string $body): void
    {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom((string) Env::get('MAIL_FROM_ADDRESS', 'accounts@example.test'), 'Bunat Fabrics');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $driver = Env::get('MAIL_DRIVER', 'disabled');
            if ($driver === 'file' && Env::get('APP_ENV') !== 'production') {
                // Deliberate local mailbox, outside the public document root and Git.
                $directory = base_path('storage/mail');
                if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
                    throw new RuntimeException('Mailbox unavailable');
                }
                $mail->preSend();
                $file = $directory . '/' . bin2hex(random_bytes(16)) . '.eml';
                $handle = fopen($file, 'x');
                if (!$handle) { throw new RuntimeException('Mailbox unavailable'); }
                chmod($file, 0600);
                fwrite($handle, $mail->getSentMIMEMessage());
                fclose($handle);
                return;
            }
            if ($driver !== 'smtp') { throw new RuntimeException('Mail unavailable'); }
            $mail->isSMTP();
            $mail->Host = (string) Env::get('MAIL_HOST', 'localhost');
            $mail->Port = (int) Env::get('MAIL_PORT', 587);
            $mail->Timeout = 10;
            $mail->SMTPAuth = Env::get('MAIL_USERNAME', '') !== '';
            $mail->Username = (string) Env::get('MAIL_USERNAME', '');
            $mail->Password = (string) Env::get('MAIL_PASSWORD', '');
            $mail->SMTPSecure = (string) Env::get('MAIL_ENCRYPTION', 'tls');
            $mail->send();
        } catch (Throwable) {
            // Do not log provider responses, message bodies, addresses or credentials.
            throw new RuntimeException('Account email could not be sent.');
        }
    }

    public function passwordChanged(string $email): void
    {
        try {
            $this->send($email, 'Your Bunat Fabrics password changed', 'Your account password was changed. If you did not make this change, contact the business immediately.');
        } catch (Throwable) {
            error_log('Account security notification delivery failed.');
        }
    }
}
