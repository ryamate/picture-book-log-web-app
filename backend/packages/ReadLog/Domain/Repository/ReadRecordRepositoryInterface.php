<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Repository;

use Packages\ReadLog\Domain\Entity\ReadRecord;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;

interface ReadRecordRepositoryInterface
{
    public function findById(ReadRecordId $id): ?ReadRecord;

    public function save(ReadRecord $record): ReadRecord;

    public function delete(ReadRecordId $id): void;
}
