<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

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
     * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
     * @SuppressWarnings("PHPMD.Superglobals")
     */
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
    public function removeCookie(string $name): bool
    {
        if (!isset($_COOKIE[$name])) {
            return false;
        }

        unset($_COOKIE[$name]);

        return setcookie($name, '', ['expires' => -1]);
    }

    public function set(CookieConfiguration $configuration, string $name, string $value): bool
    {
        return setcookie(
            $name,
            $value,
            [
                'domain' => $configuration->domain,
                'expires' => time() + $configuration->lifetime,
                'httponly' => $configuration->httpOnly,
                'path' => $configuration->path,
                'samesite' => $configuration->sameSite,
                'secure' => $configuration->secure,
            ],
        );
    }
}
