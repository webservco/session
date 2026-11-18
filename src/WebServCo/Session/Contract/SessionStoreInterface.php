<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The session, started only when something needs it: values are text or arrays, and a read or a removal never starts
 * it.
 */
interface SessionStoreInterface
{
    /**
     * An array value (eg. the flash messages); empty without a session, without the value, or when the value isn't
     * an array. The caller checks the items.
     *
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    public function getArray(string $key): array;

    /**
     * A text value; null without a session or without the value.
     */
    public function getString(string $key): ?string;

    public function isStarted(): bool;

    /**
     * Removes a value. Never starts the session: without one, there is nothing to remove.
     */
    public function remove(string $key): bool;

    /**
     * Starts the session if the request sent its cookie. A cookie with an ID the server doesn't know starts a new
     * session (strict mode).
     */
    public function resume(ServerRequestInterface $request): bool;

    /**
     * Stores an array, and starts the session first if needed. Scalars and arrays only, no objects: a session that
     * holds one can't be read once its class changes.
     *
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $value
     */
    public function setArray(string $key, array $value): bool;

    /**
     * Stores a text value, and starts the session first if needed.
     */
    public function setString(string $key, string $value): bool;
}
