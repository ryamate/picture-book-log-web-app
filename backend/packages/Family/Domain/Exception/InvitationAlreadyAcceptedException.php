<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Exception;

use RuntimeException;

final class InvitationAlreadyAcceptedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This invitation has already been accepted.');
    }
}
