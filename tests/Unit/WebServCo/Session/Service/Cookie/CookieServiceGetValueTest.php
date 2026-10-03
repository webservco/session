<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service\Cookie;

use PHPUnit\Framework\Attributes\CoversClass;
use UnexpectedValueException;
use WebServCo\Session\Service\CookieService;

#[CoversClass(CookieService::class)]
final class CookieServiceGetValueTest extends AbstractCookieServiceTester
{
    public function testCookieMissing(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, self::COOKIE_VALUE);

        // Test.

        $result = $this->getService()->getValue(self::COOKIE_NAME_MISSING);

        self::assertNull($result);
    }

    public function testCookieString(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, self::COOKIE_VALUE);

        // Test.

        $result = $this->getService()->getValue(self::COOKIE_NAME);

        self::assertSame(self::COOKIE_VALUE, $result);
    }

    public function testCookieEmptyString(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, '');

        // Test.

        $result = $this->getService()->getValue(self::COOKIE_NAME);

        self::assertSame('', $result);
    }

    public function testCookieIntegerIsCastToString(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, 123);

        // Test.

        $result = $this->getService()->getValue(self::COOKIE_NAME);

        self::assertSame('123', $result);
    }

    public function testCookieBooleanIsCastToString(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, true);

        // Test.

        $result = $this->getService()->getValue(self::COOKIE_NAME);

        self::assertSame('1', $result);
    }

    public function testCookieNullIsTreatedAsNotScalar(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, null);

        // Test.

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIs('Cookie value is not scalar.');

        $this->getService()->getValue(self::COOKIE_NAME);
    }

    public function testCookieArrayThrowsException(): void
    {
        // Config.

        // Setup.

        $this->setCookieValue(self::COOKIE_NAME, [self::COOKIE_VALUE]);

        // Test.

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIs('Cookie value is not scalar.');

        $this->getService()->getValue(self::COOKIE_NAME);
    }
}
