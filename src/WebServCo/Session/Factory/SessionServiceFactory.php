<?php

declare(strict_types=1);

namespace WebServCo\Session\Factory;

use Override;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Session\Contract\CookieConfigurationFactoryInterface;
use WebServCo\Session\Contract\SessionServiceFactoryInterface;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\Model\DataTransfer\SessionConfiguration;
use WebServCo\Session\Service\SessionService;

final readonly class SessionServiceFactory implements SessionServiceFactoryInterface
{
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
            $this->configurationGetter->getInt('SESSION_EXPIRE'),
            $this->configurationGetter->getBool('SESSION_STRICT_STORAGE_PATH'),
        );
    }
}
