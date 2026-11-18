<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

/**
 * The CSRF token of the session, and where a request carries it.
 */
interface CsrfTokenServiceInterface
{
    // The hidden field of a form.
    public const string FIELD_NAME = 'csrf_token';

    // The header of a request sent with JavaScript (eg. htmx: `hx-headers`).
    public const string HEADER_NAME = 'X-CSRF-Token';

    /**
     * The token of a session that already exists, or null: never starts a session. For `hx-headers` on `<body>`.
     */
    public function getSessionToken(): ?string;

    /**
     * The token of the session, for a form: starts the session and creates the token when needed.
     */
    public function getToken(): string;

    /**
     * Whether the token is the session's. False without a session or without a token.
     */
    public function isValid(string $token): bool;

    /**
     * A new token. At login, together with a new session ID (`SessionServiceInterface::regenerateId()`).
     */
    public function renewToken(): string;
}
