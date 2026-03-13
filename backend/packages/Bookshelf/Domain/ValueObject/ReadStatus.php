<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

enum ReadStatus: string
{
    case Unread = 'unread';
    case Reading = 'reading';
    case Read = 'read';
}
