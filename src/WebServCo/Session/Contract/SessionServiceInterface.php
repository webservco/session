<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

interface SessionServiceInterface
{
    public function assertStarted(): bool;

    public function destroy(): bool;

    /**
     * Convenience method to access $_SESSION array.
     *
     * Why: avoid multiple static analysis throughout the implementing code.
     *
     * @phpcs:disable: SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return array<non-empty-string,mixed>
     */
    public function getSessionData(): array;

    public function isStarted(): bool;

    public function setSessionDataItem(string $key, mixed $value): bool;

    public function unsetSessionDataItem(string $key): bool;

    public function start(): bool;
}
