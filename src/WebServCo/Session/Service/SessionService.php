<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use OutOfRangeException;
use UnexpectedValueException;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\DataTransfer\SessionConfiguration;

use function array_key_exists;
use function in_array;
use function ini_get;
use function ini_set;
use function session_cache_expire;
use function session_cache_limiter;
use function session_destroy;
use function session_name;
use function session_save_path;
use function session_set_cookie_params;
use function session_start;
use function session_status;
use function session_unset;
use function setcookie;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

final class SessionService implements SessionServiceInterface
{
    public function __construct(private SessionConfiguration $sessionConfiguration,)
    {
    }

    public function assertStarted(): bool
    {
        if (!$this->isStarted()) {
            throw new OutOfRangeException('Session is not started.');
        }

        return true;
    }

    public function destroy(): bool
    {
        // Session must be started.
        $this->assertStarted();

        // Clear $_SESSION array.
        session_unset();

        // Check if cookie is used to store the session id (default behavior).
        if ((bool) ini_get('session.use_cookies')) {
            // Set a cookie with the same parameters used to start the session, but with date in the past.
            setcookie(
                $this->getSessionName(),
                '',
                [
                    'domain' => $this->sessionConfiguration->cookieConfiguration->domain,
                    'expires' => $this->sessionConfiguration->cookieConfiguration->lifetime,
                    'httponly' => $this->sessionConfiguration->cookieConfiguration->httpOnly,
                    'path' => $this->sessionConfiguration->cookieConfiguration->path,
                    'samesite' => $this->sessionConfiguration->cookieConfiguration->sameSite,
                    'secure' => $this->sessionConfiguration->cookieConfiguration->secure,
                ],
            );
        }

        // Destroy session.
        session_destroy();

        return true;
    }

    /**
     * Psalm error:
     * The declared return type
     * 'array<non-empty-string, mixed>' for getSessionData is incorrect, got
     * 'array<non-empty-string, mixed>'
     * Exact same thing char by char, no idea how to fix (search keyword PSALM_SAME).
     *
     * @phpcs:disable: SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @psalm-suppress InvalidReturnType,InvalidReturnStatement
     * @SuppressWarnings(PHPMD.Superglobals)
     * @todo study Psalm fix
     * @return array<non-empty-string,mixed>
     */
    public function getSessionData(): array
    {
        $this->assertStarted();

        return $_SESSION;
    }
    // @phpcs:enable

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function setSessionDataItem(string $key, mixed $value): bool
    {
        $this->assertStarted();

        /**
         * Psalm error:
         * Unable to determine the type of this assignment.
         * However this is indeed mixed, no solution but to suppress error.
         *
         * @psalm-suppress MixedAssignment
         */
        $_SESSION[$key] = $value;

        return true;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function unsetSessionDataItem(string $key): bool
    {
        $this->assertStarted();

        /**
         * Psalm error:
         * Argument 2 of array_key_exists expects array<array-key, mixed>,
         * but possibly undefined array<non-empty-string, mixed> provided
         *
         * However there is the assertStarted, so the $_SESSION array is indeed defined.
         *
         * @psalm-suppress InvalidScalarArgument
         */
        if (!array_key_exists($key, $_SESSION)) {
            return false;
        }

        unset($_SESSION[$key]);

        return true;
    }

    public function start(?string $storagePath = null): bool
    {
        // Check CLI
        if (in_array(PHP_SAPI, ['cli', 'cgi-fcgi'], true)) {
            // Not starting in CLI mode.
            return false;
        }

        if ($this->isStarted()) {
            // Already started.
            return false;
        }

        /**
         * Set cache limiter.
         */
        session_cache_limiter('public, must-revalidate');

        $this->setCacheExpire();

        /**
         * Set garbage collector timeout (seconds).
         */
        ini_set('session.gc_maxlifetime', (string) $this->sessionConfiguration->expire);

        /**
         * Set custom session storage path.
         */
        if ($storagePath !== null) {
            $this->setStoragePath($storagePath);
        }

        /**
         * Make sure garbage collector visits us.
         */
        ini_set('session.gc_probability', '1');

        $this->setCookieParams();

        session_name('webservco');

        if (session_start() === false) {
            throw new UnexpectedValueException('Unable to start session.');
        }

        return true;
    }

    private function getSessionName(): string
    {
        $sessionName = session_name();
        if ($sessionName === false) {
            throw new UnexpectedValueException('Error getting session name.');
        }

        return $sessionName;
    }

    private function setCacheExpire(): bool
    {
        /**
         * Set cache expire (minutes).
         */
        $cacheExpire = session_cache_expire((int) ($this->sessionConfiguration->expire / 60));
        if ($cacheExpire === false) {
            throw new UnexpectedValueException('Unable to set cache expire.');
        }

        return true;
    }

    private function setCookieParams(): bool
    {
        return session_set_cookie_params([
            'domain' => $this->sessionConfiguration->cookieConfiguration->domain,
            'httponly' => $this->sessionConfiguration->cookieConfiguration->httpOnly,
            'lifetime' => $this->sessionConfiguration->cookieConfiguration->lifetime,
            'path' => $this->sessionConfiguration->cookieConfiguration->path,
            'samesite' => $this->sessionConfiguration->cookieConfiguration->sameSite,
            'secure' => $this->sessionConfiguration->cookieConfiguration->secure,
        ]);
    }

    private function setStoragePath(string $storagePath): bool
    {
        ini_set('session.save_path', $storagePath);
        $actualStoragePath = session_save_path($storagePath);

        if ($actualStoragePath !== $storagePath) {
            if ($this->sessionConfiguration->useStrictStoragePath) {
                throw new UnexpectedValueException('Unable to set custom session storage path.');
            }

            return false;
        }

        return true;
    }
}
