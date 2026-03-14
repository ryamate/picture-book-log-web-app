<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Exception;

use RuntimeException;

final class DuplicateInvitationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An invitation has already been sent to this email address.');
    }
}
