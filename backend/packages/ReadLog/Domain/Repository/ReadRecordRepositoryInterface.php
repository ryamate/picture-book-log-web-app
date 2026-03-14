<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Repository;

use Packages\ReadLog\Domain\Entity\ReadRecord;
use Packages\ReadLog\Domain\ValueObject\ReadRecordId;

/**
 * 読み聞かせ記録のリポジトリインターフェース。
 */
interface ReadRecordRepositoryInterface
{
    /**
     * IDで読み聞かせ記録を検索する。
     *
     * @param  ReadRecordId  $id 読み聞かせ記録ID
     * @return ReadRecord|null
     */
    public function findById(ReadRecordId $id): ?ReadRecord;

    /**
     * 読み聞かせ記録を保存する（新規の場合は作成、既存の場合は更新）。
     *
     * @param  ReadRecord  $record 読み聞かせ記録エンティティ
     * @return ReadRecord
     */
    public function save(ReadRecord $record): ReadRecord;

    /**
     * IDで読み聞かせ記録を削除する。
     *
     * @param  ReadRecordId  $id 読み聞かせ記録ID
     * @return void
     */
    public function delete(ReadRecordId $id): void;
}
