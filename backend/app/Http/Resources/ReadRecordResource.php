<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadRecordResource extends JsonResource
{
    /**
     * @param Request $request
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
