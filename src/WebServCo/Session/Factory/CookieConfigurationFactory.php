<?php

declare(strict_types=1);

namespace WebServCo\Session\Factory;

use OutOfRangeException;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Session\Contract\CookieConfigurationFactoryInterface;
use WebServCo\Session\Model\DataTransfer\CookieConfiguration;

use function in_array;

final class CookieConfigurationFactory implements CookieConfigurationFactoryInterface
{
    public function __construct(private readonly ConfigurationGetterInterface $configurationGetter)
    {
    }

    public function createCookieConfiguration(): CookieConfiguration
    {
        // Validation already done in CookieConfiguration constructor, done also here for phpstan.
        $sameSite = $this->configurationGetter->getString('COOKIE_SAME_SITE');
        if (!in_array($sameSite, ['Lax', 'None', 'Strict'], true)) {
            throw new OutOfRangeException('Invalid sameSite attribute.');
        }

        return new CookieConfiguration(
            $this->configurationGetter->getInt('COOKIE_LIFETIME'),
            $this->configurationGetter->getString('COOKIE_PATH'),
            $this->configurationGetter->getString('COOKIE_DOMAIN'),
            $this->configurationGetter->getBool('COOKIE_SECURE'),
            $this->configurationGetter->getBool('COOKIE_HTTP_ONLY'),
            $sameSite,
        );
    }
}
