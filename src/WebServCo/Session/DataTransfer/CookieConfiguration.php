<?php

declare(strict_types=1);

namespace WebServCo\Session\DataTransfer;

use OutOfRangeException;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;

use function in_array;

final class CookieConfiguration implements DataTransferInterface
{
    /**
     * sameSite param notation required by phpstan.
     *
     * @param 'Lax'|'None'|'Strict' $sameSite
     */
    public function __construct(
        public readonly int $lifetime,
        public readonly string $path,
        public readonly string $domain,
        public readonly bool $secure,
        public readonly bool $httpOnly,
        public readonly string $sameSite,
    ) {
        /**
         * Enforce sameSite attribute value; not required by phpstan.
         * This also makes psalm give error:
         * DocblockTypeContradiction
         * Docblock-defined type 'Lax' for $sameSite is always =string(Lax)
         *
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!in_array($sameSite, ['Lax', 'None', 'Strict'], true)) {
            throw new OutOfRangeException('Invalid sameSite attribute.');
        }
    }
}
