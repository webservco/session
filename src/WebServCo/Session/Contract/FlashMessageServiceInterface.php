<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

use WebServCo\Session\Model\Enum\FlashMessageType;

/**
 * Flash messages: the result of an action, kept in the session until the next page shows them, once.
 */
interface FlashMessageServiceInterface
{
    /**
     * Adds a message, unless the same one already waits (eg. a form sent twice).
     *
     * @param string $key Translation key of the text. Written in full, so that a catalog check finds it.
     * @param array<string,float|int|string> $parameters
     */
    public function add(FlashMessageType $type, string $key, array $parameters = []): bool;

    /**
     * The waiting messages, in the order they were added, removed from the session: each one is shown once.
     *
     * An item that isn't a message (eg. stored by an older version of this class) is dropped.
     *
     * @return list<\WebServCo\Session\Model\DataTransfer\FlashMessage>
     */
    public function take(): array;
}
