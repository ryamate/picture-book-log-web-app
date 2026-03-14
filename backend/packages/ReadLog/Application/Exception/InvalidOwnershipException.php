<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Exception;

use RuntimeException;

/**
 * リソースの所有権検証に失敗した場合の例外。
 *
 * 指定されたリソース（子ども・絵本）が家族に属していない場合にスローされる。
 */
final class InvalidOwnershipException extends RuntimeException
{
    /**
     * @param string $field   エラー対象のフィールド名
     * @param string $message エラーメッセージ
     */
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
