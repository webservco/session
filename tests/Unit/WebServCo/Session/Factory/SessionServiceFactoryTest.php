<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Factory;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Session\Contract\CookieConfigurationFactoryInterface;
use WebServCo\Session\Contract\SessionServiceInterface;
use WebServCo\Session\Factory\SessionServiceFactory;
use WebServCo\Session\Model\DataTransfer\CookieConfiguration;
use WebServCo\Session\Model\DataTransfer\SessionConfiguration;
use WebServCo\Session\Service\SessionService;

#[CoversClass(SessionServiceFactory::class)]
#[UsesClass(CookieConfiguration::class)]
#[UsesClass(SessionConfiguration::class)]
#[UsesClass(SessionService::class)]
final class SessionServiceFactoryTest extends TestCase
{
    public function testTheCookieNameComesFromTheConfiguration(): void
    {
        self::assertSame('test_session', $this->createSessionService('test_session')->getCookieName());
    }

    public function testTheCookieNameHasADefault(): void
    {
        self::assertSame('webservco', $this->createSessionService(null)->getCookieName());
    }

    /**
     * @param ?string $cookieName `COOKIE_NAME_SESSION`; null: the key is missing.
     */
    private function createSessionService(?string $cookieName): SessionServiceInterface
    {
        $cookieConfigurationFactory = self::createStub(CookieConfigurationFactoryInterface::class);
        $cookieConfigurationFactory->method('createCookieConfiguration')
            ->willReturn(new CookieConfiguration(3600, '/', '', true, true, 'Lax'));

        $configurationGetter = self::createStub(ConfigurationGetterInterface::class);
        $configurationGetter->method('getBool')->willReturn(true);
        $configurationGetter->method('getInt')->willReturn(3600);
        $configurationGetter->method('getString')->willReturnCallback(
            static function (string $key) use ($cookieName): string {
                if ($cookieName === null) {
                    throw new OutOfBoundsException('Configuration key not found: ' . $key);
                }

                return $cookieName;
            },
        );

        $sessionServiceFactory = new SessionServiceFactory($cookieConfigurationFactory, $configurationGetter);

        return $sessionServiceFactory->createSessionService();
    }
}
