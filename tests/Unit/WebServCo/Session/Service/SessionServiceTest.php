<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service;

use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\Session\Model\DataTransfer\CookieConfiguration;
use WebServCo\Session\Model\DataTransfer\SessionConfiguration;
use WebServCo\Session\Service\SessionService;

/**
 * The tests run in the CLI, where `start()` starts nothing: only what works without a session is tested here.
 */
#[CoversClass(SessionService::class)]
#[UsesClass(CookieConfiguration::class)]
#[UsesClass(SessionConfiguration::class)]
final class SessionServiceTest extends TestCase
{
    private const string COOKIE_NAME = 'test_session';

    public function testGetCookieNameReturnsTheConfiguredName(): void
    {
        self::assertSame(self::COOKIE_NAME, $this->createService()->getCookieName());
    }

    public function testStartDoesNothingInTheCli(): void
    {
        $service = $this->createService();

        self::assertFalse($service->start());
        self::assertFalse($service->isStarted());
    }

    public function testRegenerateIdRequiresAStartedSession(): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessageIs('Session is not started.');

        $this->createService()->regenerateId();
    }

    private function createService(): SessionService
    {
        return new SessionService(
            new SessionConfiguration(
                new CookieConfiguration(3600, '/', '', true, true, 'Lax'),
                self::COOKIE_NAME,
                3600,
                true,
            ),
        );
    }
}
