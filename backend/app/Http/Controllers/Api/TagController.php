<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTagRequest;
use App\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Packages\ReadLog\Application\Query\SearchTags\SearchTagsHandler;
use Packages\ReadLog\Application\Query\SearchTags\SearchTagsQuery;

/**
 * タグ検索のAPIコントローラー。
 */
class TagController extends Controller
{
    /**
     * タグをキーワードで検索する（オートコンプリート用）。 GET /api/v1/tags
     *
     * @param  SearchTagRequest  $request  タグ検索リクエスト
     * @param  SearchTagsHandler  $handler  タグ検索ハンドラー
     */
    public function index(SearchTagRequest $request, SearchTagsHandler $handler): AnonymousResourceCollection
    {
        $tags = $handler->handle(new SearchTagsQuery(
            keyword: $request->keyword(),
        ));

        return TagResource::collection($tags);
    }
}
