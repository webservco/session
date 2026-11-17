<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

use WebServCo\Session\Model\DataTransfer\CookieConfiguration;

interface CookieServiceInterface
{
    public function getValue(string $cookieName): ?string;

    /**
     * Uses the same configuration as `set()`: the browser only removes the cookie if the domain and path match.
     */
    public function removeCookie(CookieConfiguration $configuration, string $name): bool;

    public function set(CookieConfiguration $configuration, string $name, string $value): bool;
}
