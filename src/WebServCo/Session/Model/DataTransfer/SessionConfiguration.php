<?php

declare(strict_types=1);

namespace WebServCo\Session\Model\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final readonly class SessionConfiguration implements DataTransferInterface
{
    public function __construct(
        public CookieConfiguration $cookieConfiguration,
        public int $expire,
        public bool $useStrictStoragePath,
    ) {
    }
}
