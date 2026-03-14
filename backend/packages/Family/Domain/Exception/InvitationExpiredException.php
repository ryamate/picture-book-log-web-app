<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Exception;

use RuntimeException;

final class InvitationExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This invitation has expired.');
    }
}
