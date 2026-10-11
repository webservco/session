<?php

declare(strict_types=1);

namespace WebServCo\Session\Factory;

use OutOfBoundsException;
use Override;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Session\Contract\CookieConfigurationFactoryInterface;
use WebServCo\Session\Contract\SessionServiceFactoryInterface;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\Model\DataTransfer\SessionConfiguration;
use WebServCo\Session\Service\SessionService;

final readonly class SessionServiceFactory implements SessionServiceFactoryInterface
{
    private const string DEFAULT_COOKIE_NAME = 'webservco';

    public function __construct(
        private CookieConfigurationFactoryInterface $cookieConfigurationFactory,
        private ConfigurationGetterInterface $configurationGetter,
    ) {
    }

    #[Override]
    public function createSessionService(): SessionServiceInterface
    {
        return new SessionService($this->createSessionConfiguration());
    }

    private function createSessionConfiguration(): SessionConfiguration
    {
        return new SessionConfiguration(
            $this->cookieConfigurationFactory->createCookieConfiguration(),
            $this->getCookieName(),
            $this->configurationGetter->getInt('SESSION_EXPIRE'),
            $this->configurationGetter->getBool('SESSION_STRICT_STORAGE_PATH'),
        );
    }

    /**
     * Optional key: use the default name if it is missing or empty.
     */
    private function getCookieName(): string
    {
        try {
            return $this->configurationGetter->getString('COOKIE_NAME_SESSION');
        } catch (OutOfBoundsException) {
            return self::DEFAULT_COOKIE_NAME;
        }
    }
}
