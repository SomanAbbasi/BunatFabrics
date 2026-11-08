<?php

declare(strict_types=1);

class TestAccountMailer extends App\Auth\AccountMailer
{
    public array $messages = [];
    public function send(string $email, string $subject, string $body): void
    {
        $this->messages[] = compact('email', 'subject', 'body');
    }
}
