<?php

declare(strict_types=1);

namespace WebServCo\Session\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final class CookieConfiguration implements DataTransferInterface
{
    public function __construct(
        public readonly int $lifetime,
        public readonly string $path,
        public readonly string $domain,
        public readonly bool $secure,
        public readonly bool $httponly,
        public readonly string $samesite,
    ) {
    }
}
