<?php

declare(strict_types=1);

namespace WebServCo\Session\Contract;

interface SessionServiceFactoryInterface
{
    public function createSessionService(): SessionServiceInterface;
}
