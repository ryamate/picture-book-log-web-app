<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 単一の読み聞かせ記録のAPIリソース。
 *
 * JSON構造: {id, picture_book: {id, title, thumbnail_url}, read_date, memo, children: [{id, name, reaction}], tags: [{id, name}], recorded_by: {id, name}, created_at}
 */
class ReadRecordResource extends JsonResource
{
    /**
     * リソースを配列に変換する。
     *
     * @param  Request  $request  リクエスト
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'picture_book' => [
                'id' => $this->pictureBook->id,
                'title' => $this->pictureBook->title,
                'thumbnail_url' => $this->pictureBook->thumbnail_url,
            ],
            'read_date' => $this->read_date->format('Y-m-d'),
            'memo' => $this->memo,
            'children' => $this->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'reaction' => $child->pivot->reaction,
            ]),
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'recorded_by' => $this->recordedByUser ? [
                'id' => $this->recordedByUser->id,
                'name' => $this->recordedByUser->name,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
