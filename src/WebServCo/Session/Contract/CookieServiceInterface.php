<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

use WebServCo\Session\Model\DataTransfer\CookieConfiguration;

interface CookieServiceInterface
{
    public function getValue(string $cookieName): ?string;

    public function removeCookie(string $name): bool;

    public function set(CookieConfiguration $configuration, string $name, string $value): bool;
}
