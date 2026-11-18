<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use WebServCo\Data\Contract\Extraction\Strict\StrictArrayNonEmptyDataExtractionServiceInterface;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\Contract\SessionStoreInterface;

use function array_key_exists;
use function is_array;

/**
 * The session, started only when something needs it:
 * - resumed when the request sends the session cookie (`resume()`, called by the session middleware of
 *   `webservco/middleware`);
 * - started when a value is stored: a form's CSRF token, a flash message, the login;
 * - never started by a read or a removal: without a session, every value is missing.
 *
 * Values are text (`getString()`, `setString()`) or arrays (`getArray()`, `setArray()`).
 *
 * A visitor who only reads pages gets no session. The session files go into the storage path given here.
 */
final class SessionStore implements SessionStoreInterface
{
    public function __construct(
        private StrictArrayNonEmptyDataExtractionServiceInterface $dataExtractionService,
        private SessionServiceInterface $sessionService,
        private string $storagePath,
    ) {
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    #[Override]
    public function getArray(string $key): array
    {
        if (!$this->sessionService->isStarted()) {
            return [];
        }

        $data = $this->sessionService->getSessionData();
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        return $data[$key];
    }

    #[Override]
    public function getString(string $key): ?string
    {
        if (!$this->sessionService->isStarted()) {
            return null;
        }

        return $this->dataExtractionService->getNonEmptyNullableString($this->sessionService->getSessionData(), $key);
    }

    #[Override]
    public function isStarted(): bool
    {
        return $this->sessionService->isStarted();
    }

    #[Override]
    public function remove(string $key): bool
    {
        if (!$this->sessionService->isStarted()) {
            return false;
        }

        return $this->sessionService->unsetSessionDataItem($key);
    }

    #[Override]
    public function resume(ServerRequestInterface $request): bool
    {
        if (!array_key_exists($this->sessionService->getCookieName(), $request->getCookieParams())) {
            return false;
        }

        return $this->start();
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $value
     */
    #[Override]
    public function setArray(string $key, array $value): bool
    {
        $this->start();

        return $this->sessionService->setSessionDataItem($key, $value);
    }

    #[Override]
    public function setString(string $key, string $value): bool
    {
        $this->start();

        return $this->sessionService->setSessionDataItem($key, $value);
    }

    private function start(): bool
    {
        if ($this->sessionService->isStarted()) {
            return false;
        }

        return $this->sessionService->start($this->storagePath);
    }
}
