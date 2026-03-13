<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

/**
 * 読書状況を表す列挙型。
 */
enum ReadStatus: string
{
    /** 未読 */
    case Unread = 'unread';

    /** 読書中 */
    case Reading = 'reading';

    /** 読了 */
    case Read = 'read';
}
