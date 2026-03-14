<?php

declare(strict_types=1);

namespace Packages\Bookshelf\Domain\ValueObject;

/**
 * 著者一覧を表す値オブジェクト。
 */
final class Authors
{
    /** @param string[] $value */
    public function __construct(private readonly array $value) {}

    /** @return string[] */
    public function toArray(): array
    {
        return $this->value;
    }
}
