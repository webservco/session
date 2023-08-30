<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use UnexpectedValueException;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\DataTransfer\SessionConfiguration;

use function ini_set;
use function session_cache_expire;
use function session_cache_limiter;
use function session_name;
use function session_save_path;
use function session_set_cookie_params;
use function session_start;
use function session_status;

use const PHP_SAPI;
use const PHP_SESSION_ACTIVE;

final class SessionService implements SessionServiceInterface
{
    public function __construct(private SessionConfiguration $sessionConfiguration)
    {
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function start(?string $storagePath = null): bool
    {
        // Check CLI
        if (PHP_SAPI === 'cli') {
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
            'httponly' => $this->sessionConfiguration->cookieConfiguration->httponly,
            'lifetime' => $this->sessionConfiguration->cookieConfiguration->lifetime,
            'path' => $this->sessionConfiguration->cookieConfiguration->path,
            'samesite' => $this->sessionConfiguration->cookieConfiguration->samesite,
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
