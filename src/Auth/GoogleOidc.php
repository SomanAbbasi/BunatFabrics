<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Env;
use Jumbojett\OpenIDConnectClient;

/** Tighten required Google claims beyond the general-purpose library defaults. */
final class GoogleOidc extends OpenIDConnectClient
{
    public function __construct()
    {
        parent::__construct('https://accounts.google.com', (string) Env::get('GOOGLE_CLIENT_ID', ''), (string) Env::get('GOOGLE_CLIENT_SECRET', ''));
        $this->setRedirectURL((string) Env::get('GOOGLE_REDIRECT_URI', 'http://localhost/auth/google/callback'));
        $this->addScope(['openid', 'email', 'profile']);
        $this->setCodeChallengeMethod('S256');
        $this->providerConfigParam(['code_challenge_methods_supported' => ['S256']]);
        $this->setAllowImplicitFlow(false);
        $this->setTimeout(10);
    }

    protected function verifyJWTClaims($claims, string $accessToken = null): bool
    {
        return isset($claims->nonce, $claims->exp, $claims->iat, $claims->sub, $claims->aud, $claims->iss)
            && is_int($claims->exp) && $claims->exp > time()
            && is_int($claims->iat) && $claims->iat <= time() + 60
            && is_string($claims->nonce) && hash_equals((string) $this->getNonce(), $claims->nonce)
            && (!isset($claims->azp) || $claims->azp === Env::get('GOOGLE_CLIENT_ID'))
            && parent::verifyJWTClaims($claims, $accessToken);
    }

    public static function enabled(): bool
    {
        return Env::get('GOOGLE_CLIENT_ID', '') !== '' && Env::get('GOOGLE_CLIENT_SECRET', '') !== '';
    }
}
