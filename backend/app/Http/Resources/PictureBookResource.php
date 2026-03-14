<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 単一の絵本のAPIリソース。
 *
 * JSON構造: {id, google_books_id, isbn, title, authors, thumbnail_url, rating, read_status, review, registered_by: {id, name}, created_at}
 */
class PictureBookResource extends JsonResource
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
            'google_books_id' => $this->google_books_id,
            'isbn' => $this->isbn,
            'title' => $this->title,
            'authors' => $this->authors,
            'thumbnail_url' => $this->thumbnail_url,
            'rating' => $this->rating,
            'read_status' => $this->read_status,
            'review' => $this->review,
            'registered_by' => $this->registeredByUser ? [
                'id' => $this->registeredByUser->id,
                'name' => $this->registeredByUser->name,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
