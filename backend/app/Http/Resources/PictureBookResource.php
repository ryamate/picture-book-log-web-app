<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PictureBookResource extends JsonResource
{
    /** @return array<string, mixed> */
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
