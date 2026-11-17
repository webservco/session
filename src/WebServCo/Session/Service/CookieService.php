<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use Override;
use UnexpectedValueException;
use WebServCo\Session\Contract\CookieServiceInterface;
use WebServCo\Session\Model\DataTransfer\CookieConfiguration;

use function array_key_exists;
use function is_scalar;
use function setcookie;
use function time;

final class CookieService implements CookieServiceInterface
{
    /**
     * Psalm errors:
     * Type string for $_COOKIE[$cookieName] is always !scalar (see https://psalm.dev/056)
     * Redundant cast to string (see https://psalm.dev/262)
     * Psalm types cookie values as string, however they can also be arrays (eg. "name[]=value"),
     * and the superglobal can be modified at runtime, so both the check and the cast are needed.
     *
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @psalm-suppress TypeDoesNotContainType, RedundantCast
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    #[Override]
    public function getValue(string $cookieName): ?string
    {
        if (array_key_exists($cookieName, $_COOKIE)) {
            if (!is_scalar($_COOKIE[$cookieName])) {
                throw new UnexpectedValueException('Cookie value is not scalar.');
            }

            return (string) $_COOKIE[$cookieName];
        }

        return null;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    #[Override]
    public function removeCookie(CookieConfiguration $configuration, string $name): bool
    {
        if (!isset($_COOKIE[$name])) {
            return false;
        }

        unset($_COOKIE[$name]);

        /**
         * The browser only removes the cookie if the domain and path match the ones it was set with.
         * For an empty value PHP sends `deleted`, a 1970 date and `Max-Age=0` anyway, whatever `expires` is.
         */
        return setcookie($name, '', $this->createOptions($configuration, 1));
    }

    #[Override]
    public function set(CookieConfiguration $configuration, string $name, string $value): bool
    {
        /**
         * Lifetime 0: a session cookie (no `expires`), kept until the browser closes.
         * `time() + 0` would instead send `Max-Age=0`, and the browser would drop the cookie right away.
         */
        $expires = $configuration->lifetime > 0
            ? time() + $configuration->lifetime
            : 0;

        return setcookie($name, $value, $this->createOptions($configuration, $expires));
    }

    /**
     * @return array{
     *  domain: string,
     *  expires: int,
     *  httponly: bool,
     *  path: string,
     *  samesite: 'Lax'|'None'|'Strict',
     *  secure: bool,
     * }
     */
    private function createOptions(CookieConfiguration $configuration, int $expires): array
    {
        return [
            'domain' => $configuration->domain,
            'expires' => $expires,
            'httponly' => $configuration->httpOnly,
            'path' => $configuration->path,
            'samesite' => $configuration->sameSite,
            'secure' => $configuration->secure,
        ];
    }
}
