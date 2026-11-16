<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

interface SessionServiceInterface
{
    public function assertStarted(): bool;

    public function destroy(): bool;

    /**
     * Name of the session cookie (`COOKIE_NAME_SESSION`): tells whether a request sent one.
     */
    public function getCookieName(): string;

    /**
     * Convenience method to access $_SESSION array.
     *
     * Why: avoid multiple static analysis error throughout the implementing code.
     *
     * @phpcs:disable: SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    public function getSessionData(): array;

    public function isStarted(): bool;

    /**
     * New session ID for the same data; the old session is deleted.
     *
     * Call it when the user's privileges change (login), so that an ID known before can't be used after.
     */
    public function regenerateId(): bool;

    public function setSessionDataItem(string $key, mixed $value): bool;

    public function unsetSessionDataItem(string $key): bool;

    public function start(?string $storagePath = null): bool;
}
