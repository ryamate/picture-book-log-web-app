<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * ReadRecordResourceのページネーション付きコレクション。
 *
 * JSON構造: {data: ReadRecordResource[], meta: {current_page, last_page, per_page, total}}
 */
class ReadRecordCollection extends ResourceCollection
{
    /** @var string */
    public $collects = ReadRecordResource::class;

    /**
     * レスポンスに含めるページネーションメタデータをカスタマイズする。
     *
     * @param mixed $request リクエスト
     * @param array $paginated ページネーション情報
     * @param array $default デフォルト値
     * @return array
     */
    public function paginationInformation($request, $paginated, $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
            ],
        ];
    }
}
