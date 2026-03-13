<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * PictureBookResourceのページネーション付きコレクション。
 *
 * JSON構造: {data: PictureBookResource[], meta: {current_page, last_page, per_page, total}}
 */
class PictureBookCollection extends ResourceCollection
{
    /** @var string */
    public $collects = PictureBookResource::class;

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
