<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Packages\ReadLog\Application\Query\SearchTags\SearchTagsHandler;
use Packages\ReadLog\Application\Query\SearchTags\SearchTagsQuery;

class TagController extends Controller
{
    /**
     * タグをキーワードで検索する（オートコンプリート用）。
     */
    public function index(Request $request, SearchTagsHandler $handler)
    {
        $request->validate(['q' => ['required', 'string', 'min:1']]);

        $tags = $handler->handle(new SearchTagsQuery(
            keyword: $request->query('q'),
        ));

        return TagResource::collection($tags);
    }
}
