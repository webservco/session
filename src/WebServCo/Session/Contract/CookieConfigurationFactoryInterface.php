<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

use WebServCo\Session\Model\DataTransfer\CookieConfiguration;

interface CookieConfigurationFactoryInterface
{
    public function createCookieConfiguration(): CookieConfiguration;
}
