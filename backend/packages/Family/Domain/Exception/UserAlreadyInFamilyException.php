<?php

declare(strict_types=1);

namespace Packages\Family\Domain\Exception;

use RuntimeException;

final class UserAlreadyInFamilyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This user already belongs to a family.');
    }
}
