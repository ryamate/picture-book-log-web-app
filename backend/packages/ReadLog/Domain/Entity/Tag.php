<?php

declare(strict_types=1);

namespace Packages\ReadLog\Domain\Entity;

use Packages\ReadLog\Domain\ValueObject\TagId;

/**
 * タグエンティティ。
 *
 * 読み聞かせ記録に付与するタグを表す。
 */
final class Tag
{
    private function __construct(
        private readonly ?TagId $id,
        private readonly string $name,
    ) {}

    /**
     * 新しいタグを作成する。
     */
    public static function create(string $name): self
    {
        return new self(null, $name);
    }

    /**
     * 永続化層からタグを再構築する。
     */
    public static function reconstruct(TagId $id, string $name): self
    {
        return new self($id, $name);
    }

    public function id(): ?TagId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }
}
