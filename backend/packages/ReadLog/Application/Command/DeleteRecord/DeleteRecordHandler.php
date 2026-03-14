<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\DeleteRecord;

use Packages\ReadLog\Domain\Repository\ReadRecordRepositoryInterface;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;

/**
 * 読み聞かせ記録の削除を行う。
 */
final readonly class DeleteRecordHandler
{
    public function __construct(
        private ReadRecordRepositoryInterface $readRecordRepository,
    ) {}

    /**
     * 読み聞かせ記録を削除する。
     */
    public function handle(DeleteRecordCommand $command): void
    {
        $this->readRecordRepository->delete(new ReadRecordId($command->recordId));
    }
}
