<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Session\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\Data\Container\Extraction\DataExtractionContainer;
use WebServCo\Session\Model\DataTransfer\FlashMessage;
use WebServCo\Session\Model\Enum\FlashMessageType;
use WebServCo\Session\Service\FlashMessageService;
use WebServCo\Session\Service\SessionStore;

#[CoversClass(FlashMessageService::class)]
#[CoversClass(FlashMessage::class)]
#[UsesClass(SessionStore::class)]
final class FlashMessageServiceTest extends TestCase
{
    private const string SESSION_KEY = 'flash_messages';

    public function testAddingAMessageStartsTheSession(): void
    {
        $sessionService = new InMemorySessionService();
        $flashMessageService = $this->createFlashMessageService($sessionService);

        self::assertTrue($flashMessageService->add(FlashMessageType::Success, 'log.play.added', ['title' => 'A']));

        self::assertTrue($sessionService->isStarted());
    }

    public function testMessagesAreTakenInTheOrderTheyWereAddedAndShownOnce(): void
    {
        $flashMessageService = $this->createFlashMessageService(new InMemorySessionService());
        $flashMessageService->add(FlashMessageType::Success, 'log.play.added', ['title' => 'Abbey Road', 'count' => 2]);
        $flashMessageService->add(FlashMessageType::Danger, 'log.play.failed');

        self::assertEquals(
            [
                new FlashMessage(FlashMessageType::Success, 'log.play.added', ['title' => 'Abbey Road', 'count' => 2]),
                new FlashMessage(FlashMessageType::Danger, 'log.play.failed'),
            ],
            $flashMessageService->take(),
        );
        self::assertSame([], $flashMessageService->take());
    }

    public function testTheSameMessageIsAddedOnce(): void
    {
        $flashMessageService = $this->createFlashMessageService(new InMemorySessionService());

        self::assertTrue($flashMessageService->add(FlashMessageType::Info, 'key', ['value' => 'a']));
        self::assertFalse($flashMessageService->add(FlashMessageType::Info, 'key', ['value' => 'a']));
        self::assertTrue($flashMessageService->add(FlashMessageType::Info, 'key', ['value' => 'b']));
        self::assertTrue($flashMessageService->add(FlashMessageType::Warning, 'key', ['value' => 'a']));

        self::assertCount(3, $flashMessageService->take());
    }

    public function testTakingWithoutASessionDoesNotStartOne(): void
    {
        $sessionService = new InMemorySessionService();
        $flashMessageService = $this->createFlashMessageService($sessionService);

        self::assertSame([], $flashMessageService->take());
        self::assertFalse($sessionService->isStarted());
    }

    public function testStoredItemsThatAreNotMessagesAreDropped(): void
    {
        $sessionService = new InMemorySessionService();
        $sessionService->start();
        $sessionService->setSessionDataItem(self::SESSION_KEY, [
            'text',
            ['type' => 'unknown', 'key' => 'key', 'parameters' => []],
            ['type' => 'info', 'key' => '', 'parameters' => []],
            ['type' => 'info', 'key' => 'key'],
            ['type' => 'info', 'key' => 'key', 'parameters' => ['a']],
            ['type' => 'info', 'key' => 'key', 'parameters' => ['name' => true]],
            ['type' => 'info', 'key' => 'key', 'parameters' => ['name' => 1.5]],
        ]);
        $flashMessageService = $this->createFlashMessageService($sessionService);

        self::assertEquals(
            [new FlashMessage(FlashMessageType::Info, 'key', ['name' => 1.5])],
            $flashMessageService->take(),
        );
        self::assertSame([], $sessionService->getSessionData());
    }

    public function testAStoredValueThatIsNotAListIsDropped(): void
    {
        $sessionService = new InMemorySessionService();
        $sessionService->start();
        $sessionService->setSessionDataItem(self::SESSION_KEY, 'text');

        self::assertSame([], $this->createFlashMessageService($sessionService)->take());
    }

    private function createFlashMessageService(InMemorySessionService $sessionService): FlashMessageService
    {
        $dataExtractionContainer = new DataExtractionContainer(false);

        return new FlashMessageService(
            new SessionStore(
                $dataExtractionContainer->getStrictArrayNonEmptyDataExtractionService(),
                $sessionService,
                '/project/var/sessions',
            ),
        );
    }
}
