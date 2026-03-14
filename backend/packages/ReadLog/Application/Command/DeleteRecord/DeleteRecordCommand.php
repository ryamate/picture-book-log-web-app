<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Command\DeleteRecord;

/**
 * 読み聞かせ記録削除コマンドDTO。
 */
final readonly class DeleteRecordCommand
{
    /**
     * @param  int  $recordId  読み聞かせ記録ID
     */
    public function __construct(
        public int $recordId,
    ) {}
}
