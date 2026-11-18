<?php

declare(strict_types=1);

namespace WebServCo\Session\Model\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;
use WebServCo\Session\Model\Enum\FlashMessageType;

/**
 * A flash message (`FlashMessageServiceInterface`): its type, and the translation key and parameters of its text.
 * Translated when it is shown, in the language of that page.
 */
final readonly class FlashMessage implements DataTransferInterface
{
    /**
     * @param array<string,float|int|string> $parameters
     */
    public function __construct(public FlashMessageType $type, public string $key, public array $parameters = [])
    {
    }
}
