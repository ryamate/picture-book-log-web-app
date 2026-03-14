<?php

declare(strict_types=1);

namespace Packages\ReadLog\Application\Query\SearchTags;

use App\Models\Tag;
use Illuminate\Support\Collection;

/**
 * キーワードによるタグの前方一致検索を行う。
 */
final class SearchTagsHandler
{
    public function handle(SearchTagsQuery $query): Collection
    {
        return Tag::where('name', 'like', $query->keyword . '%')
            ->limit($query->limit)
            ->get();
    }
}
