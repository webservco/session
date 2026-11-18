<?php

declare(strict_types=1);

namespace WebServCo\Session\Model\Enum;

/**
 * The type of a flash message (`FlashMessageServiceInterface`): its colour in the layout.
 *
 * The values are the names of the contextual colours of Tabler and Bootstrap (`alert-success`), and they are stored in
 * the session: renaming one drops the messages that wait in sessions.
 */
enum FlashMessageType: string
{
    case Danger = 'danger';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
}
