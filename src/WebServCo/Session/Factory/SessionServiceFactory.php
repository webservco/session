<?php

declare(strict_types=1);

namespace WebServCo\Session\Factory;

use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Session\Contract\SessionServiceFactoryInterface;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\DataTransfer\CookieConfiguration;
use WebServCo\Session\DataTransfer\SessionConfiguration;
use WebServCo\Session\Service\SessionService;

final class SessionServiceFactory implements SessionServiceFactoryInterface
{
    public function __construct(private ConfigurationGetterInterface $configurationGetter)
    {
    }

    public function createSessionService(): SessionServiceInterface
    {
        return new SessionService($this->createSessionConfiguration());
    }

    private function createCookieConfiguration(): CookieConfiguration
    {
        return new CookieConfiguration(
            $this->configurationGetter->getInt('COOKIE_LIFETIME'),
            $this->configurationGetter->getString('COOKIE_PATH'),
            $this->configurationGetter->getString('COOKIE_DOMAIN'),
            $this->configurationGetter->getBool('COOKIE_SECURE'),
            $this->configurationGetter->getBool('COOKIE_HTTP_ONLY'),
            $this->configurationGetter->getString('COOKIE_SAME_SITE'),
        );
    }

    private function createSessionConfiguration(): SessionConfiguration
    {
        return new SessionConfiguration(
            $this->createCookieConfiguration(),
            $this->configurationGetter->getInt('SESSION_EXPIRE'),
            $this->configurationGetter->getBool('SESSION_STRICT_STORAGE_PATH'),
        );
    }
}
