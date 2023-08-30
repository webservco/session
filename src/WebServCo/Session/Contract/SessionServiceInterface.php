<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

interface SessionServiceInterface
{
    public function isStarted(): bool;

    public function start(): bool;
}
