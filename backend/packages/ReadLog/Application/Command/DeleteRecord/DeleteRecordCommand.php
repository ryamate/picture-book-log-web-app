<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\DeleteRecord;

/**
 * 読み聞かせ記録削除コマンドDTO。
 */
final class DeleteRecordCommand
{
    public function __construct(
        public readonly int $recordId,
    ) {}
}
