<?php

declare(strict_types=1);

namespace WebServCo\Session\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final class SessionConfiguration implements DataTransferInterface
{
    public function __construct(
        public readonly CookieConfiguration $cookieConfiguration,
        public readonly int $expire,
        public readonly bool $useStrictStoragePath,
    ) {
    }
}
