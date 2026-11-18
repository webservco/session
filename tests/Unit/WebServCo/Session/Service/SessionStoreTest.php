<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use WebServCo\Data\Container\Extraction\DataExtractionContainer;
use WebServCo\Session\Service\SessionStore;

#[CoversClass(SessionStore::class)]
final class SessionStoreTest extends TestCase
{
    private const string STORAGE_PATH = '/project/var/sessions';

    public function testAReadDoesNotStartTheSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());

        self::assertNull($sessionStore->getString('key'));
        self::assertFalse($sessionStore->isStarted());
    }

    public function testAWriteStartsTheSessionInTheStoragePath(): void
    {
        $sessionService = new InMemorySessionService();
        $sessionStore = $this->createSessionStore($sessionService);

        self::assertTrue($sessionStore->setString('key', 'value'));

        self::assertTrue($sessionStore->isStarted());
        self::assertSame(self::STORAGE_PATH, $sessionService->getStoragePath());
        self::assertSame('value', $sessionStore->getString('key'));
        self::assertNull($sessionStore->getString('other'));
    }

    public function testASecondWriteKeepsTheSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());
        $sessionStore->setString('key', 'one');
        $sessionStore->setString('key', 'two');

        self::assertSame('two', $sessionStore->getString('key'));
    }

    public function testARequestWithTheSessionCookieResumesTheSession(): void
    {
        $sessionService = new InMemorySessionService();
        $sessionStore = $this->createSessionStore($sessionService);

        self::assertTrue($sessionStore->resume($this->createRequest([InMemorySessionService::COOKIE_NAME => 'x'])));

        self::assertTrue($sessionStore->isStarted());
        self::assertSame(self::STORAGE_PATH, $sessionService->getStoragePath());
    }

    public function testARequestWithoutTheSessionCookieStartsNoSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());

        self::assertFalse($sessionStore->resume($this->createRequest(['lang' => 'de'])));

        self::assertFalse($sessionStore->isStarted());
    }

    public function testAStartedSessionIsNotStartedAgain(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());
        $sessionStore->setString('key', 'value');

        self::assertFalse($sessionStore->resume($this->createRequest([InMemorySessionService::COOKIE_NAME => 'x'])));
        self::assertSame('value', $sessionStore->getString('key'));
    }

    public function testAnArrayIsStoredAndStartsTheSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());

        self::assertTrue($sessionStore->setArray('list', [['a' => 1], 'b']));

        self::assertTrue($sessionStore->isStarted());
        self::assertSame([['a' => 1], 'b'], $sessionStore->getArray('list'));
    }

    public function testAnArrayReadDoesNotStartTheSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());

        self::assertSame([], $sessionStore->getArray('list'));
        self::assertFalse($sessionStore->isStarted());
    }

    public function testAMissingArrayOrAValueThatIsNotAnArrayIsEmpty(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());
        $sessionStore->setString('text', 'value');

        self::assertSame([], $sessionStore->getArray('text'));
        self::assertSame([], $sessionStore->getArray('missing'));
    }

    public function testARemovedValueIsMissing(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());
        $sessionStore->setArray('list', ['a']);

        self::assertTrue($sessionStore->remove('list'));

        self::assertSame([], $sessionStore->getArray('list'));
        self::assertFalse($sessionStore->remove('list'));
    }

    public function testARemovalDoesNotStartTheSession(): void
    {
        $sessionStore = $this->createSessionStore(new InMemorySessionService());

        self::assertFalse($sessionStore->remove('list'));
        self::assertFalse($sessionStore->isStarted());
    }

    /**
     * @param array<string,string> $cookieParams
     */
    private function createRequest(array $cookieParams): ServerRequestInterface
    {
        $request = self::createStub(ServerRequestInterface::class);
        $request->method('getCookieParams')->willReturn($cookieParams);

        return $request;
    }

    private function createSessionStore(InMemorySessionService $sessionService): SessionStore
    {
        $dataExtractionContainer = new DataExtractionContainer(false);

        return new SessionStore(
            $dataExtractionContainer->getStrictArrayNonEmptyDataExtractionService(),
            $sessionService,
            self::STORAGE_PATH,
        );
    }
}
