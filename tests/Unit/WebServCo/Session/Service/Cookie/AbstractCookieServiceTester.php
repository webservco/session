<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service\Cookie;

use Override;
use PHPUnit\Framework\TestCase;
use WebServCo\Session\Contract\CookieServiceInterface;
use WebServCo\Session\Service\CookieService;

/**
 * @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
 * @SuppressWarnings("PHPMD.Superglobals")
 */
abstract class AbstractCookieServiceTester extends TestCase
{
    protected const string COOKIE_NAME = 'test_cookie';

    protected const string COOKIE_NAME_MISSING = 'missing_cookie';

    protected const string COOKIE_VALUE = 'test_value';

    /**
     * Backup of the cookie superglobal, restored after each test.
     *
     * @var array<array<string>|string>
     */
    private array $cookieBackup = [];

    private ?CookieServiceInterface $cookieService = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * PHPStan types the cookie superglobal as array<mixed>; real cookie values are strings or arrays of strings.
         *
         * @var array<array<string>|string> $cookieBackup
         */
        $cookieBackup = $_COOKIE;
        $this->cookieBackup = $cookieBackup;
        $_COOKIE = [];
    }

    #[Override]
    protected function tearDown(): void
    {
        $_COOKIE = $this->cookieBackup;

        parent::tearDown();
    }

    protected function getService(): CookieServiceInterface
    {
        if ($this->cookieService === null) {
            $this->cookieService = new CookieService();
        }

        return $this->cookieService;
    }

    /**
     * Value type is wider than a real cookie value, in order to test non-string handling.
     *
     * @param array<string>|bool|int|string|null $value
     */
    protected function setCookieValue(string $cookieName, array|bool|int|string|null $value): void
    {
        $_COOKIE[$cookieName] = $value;
    }
}
