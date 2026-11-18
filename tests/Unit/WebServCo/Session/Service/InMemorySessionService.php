<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service;

use OutOfRangeException;
use Override;
use WebServCo\Session\Contract\SessionServiceInterface;

use function array_key_exists;

/**
 * A session in memory, for the tests: PHP's own session doesn't start in the CLI (`SessionService::start()`).
 */
final class InMemorySessionService implements SessionServiceInterface
{
    public const string COOKIE_NAME = 'sid';

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @var array<mixed>
     */
    private array $data = [];

    private bool $isStarted = false;

    // Storage path of the start; null: not started.
    private ?string $storagePath = null;

    #[Override]
    public function assertStarted(): bool
    {
        if (!$this->isStarted) {
            throw new OutOfRangeException('Session is not started.');
        }

        return true;
    }

    #[Override]
    public function destroy(): bool
    {
        $this->assertStarted();
        $this->data = [];
        $this->isStarted = false;

        return true;
    }

    #[Override]
    public function getCookieName(): string
    {
        return self::COOKIE_NAME;
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    #[Override]
    public function getSessionData(): array
    {
        $this->assertStarted();

        return $this->data;
    }

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    #[Override]
    public function isStarted(): bool
    {
        return $this->isStarted;
    }

    #[Override]
    public function regenerateId(): bool
    {
        return $this->assertStarted();
    }

    #[Override]
    public function setSessionDataItem(string $key, mixed $value): bool
    {
        $this->assertStarted();
        $this->data[$key] = $value;

        return true;
    }

    #[Override]
    public function unsetSessionDataItem(string $key): bool
    {
        $this->assertStarted();
        if (!array_key_exists($key, $this->data)) {
            return false;
        }

        unset($this->data[$key]);

        return true;
    }

    #[Override]
    public function start(?string $storagePath = null): bool
    {
        if ($this->isStarted) {
            return false;
        }

        $this->isStarted = true;
        $this->storagePath = $storagePath;

        return true;
    }
}
