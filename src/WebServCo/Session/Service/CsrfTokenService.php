<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use Override;
use WebServCo\Session\Contract\CsrfTokenServiceInterface;
use WebServCo\Session\Contract\SessionStoreInterface;

use function bin2hex;
use function hash_equals;
use function random_bytes;

/**
 * CSRF token: one per session, renewed at login.
 *
 * Forms carry it in a hidden field (`FIELD_NAME`), htmx requests in a header (`HEADER_NAME`, set with `hx-headers`
 * on `<body>` of pages with a session). The CSRF middleware of `webservco/middleware` checks it on every request that
 * can change state.
 */
final class CsrfTokenService implements CsrfTokenServiceInterface
{
    private const string SESSION_KEY = 'csrf_token';

    // 64 hexadecimal characters.
    private const int TOKEN_BYTES = 32;

    public function __construct(private SessionStoreInterface $sessionStore)
    {
    }

    #[Override]
    public function getSessionToken(): ?string
    {
        if (!$this->sessionStore->isStarted()) {
            return null;
        }

        return $this->getToken();
    }

    #[Override]
    public function getToken(): string
    {
        return $this->sessionStore->getString(self::SESSION_KEY) ?? $this->renewToken();
    }

    #[Override]
    public function isValid(string $token): bool
    {
        $sessionToken = $this->sessionStore->getString(self::SESSION_KEY);

        return $sessionToken !== null && hash_equals($sessionToken, $token);
    }

    #[Override]
    public function renewToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $this->sessionStore->setString(self::SESSION_KEY, $token);

        return $token;
    }
}
