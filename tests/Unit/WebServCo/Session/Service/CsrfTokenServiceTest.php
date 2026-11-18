<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\Data\Container\Extraction\DataExtractionContainer;
use WebServCo\Session\Service\CsrfTokenService;
use WebServCo\Session\Service\SessionStore;

#[CoversClass(CsrfTokenService::class)]
#[UsesClass(SessionStore::class)]
final class CsrfTokenServiceTest extends TestCase
{
    public function testWithoutASessionThereIsNoTokenAndNoSessionStarts(): void
    {
        $sessionStore = $this->createSessionStore();
        $csrfTokenService = new CsrfTokenService($sessionStore);

        self::assertNull($csrfTokenService->getSessionToken());
        self::assertFalse($csrfTokenService->isValid('anything'));
        self::assertFalse($sessionStore->isStarted());
    }

    public function testAFormTokenStartsTheSessionAndStaysTheSame(): void
    {
        $sessionStore = $this->createSessionStore();
        $csrfTokenService = new CsrfTokenService($sessionStore);

        $token = $csrfTokenService->getToken();

        self::assertTrue($sessionStore->isStarted());
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        self::assertSame($token, $csrfTokenService->getToken());
        self::assertSame($token, $csrfTokenService->getSessionToken());
    }

    public function testASessionWithoutATokenGetsOneForHtmx(): void
    {
        $sessionStore = $this->createSessionStore();
        $sessionStore->setString('other', 'value');
        $csrfTokenService = new CsrfTokenService($sessionStore);

        $token = $csrfTokenService->getSessionToken();
        if ($token === null) {
            self::fail('A session has a token.');
        }

        self::assertTrue($csrfTokenService->isValid($token));
    }

    public function testOnlyTheTokenOfTheSessionIsValid(): void
    {
        $csrfTokenService = new CsrfTokenService($this->createSessionStore());
        $token = $csrfTokenService->getToken();

        self::assertTrue($csrfTokenService->isValid($token));
        self::assertFalse($csrfTokenService->isValid(''));
        self::assertFalse($csrfTokenService->isValid($token . '0'));
    }

    public function testARenewedTokenReplacesTheOldOne(): void
    {
        $csrfTokenService = new CsrfTokenService($this->createSessionStore());
        $oldToken = $csrfTokenService->getToken();

        $newToken = $csrfTokenService->renewToken();

        self::assertNotSame($oldToken, $newToken);
        self::assertFalse($csrfTokenService->isValid($oldToken));
        self::assertTrue($csrfTokenService->isValid($newToken));
        self::assertSame($newToken, $csrfTokenService->getToken());
    }

    private function createSessionStore(): SessionStore
    {
        $dataExtractionContainer = new DataExtractionContainer(false);

        return new SessionStore(
            $dataExtractionContainer->getStrictArrayNonEmptyDataExtractionService(),
            new InMemorySessionService(),
            '/project/var/sessions',
        );
    }
}
